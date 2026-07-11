<?php

declare(strict_types=1);

namespace Tests\Feature\Scorecard;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\Scorecard\Infrastructure\Http\Livewire\ManageMonitor;
use Modules\Scorecard\Infrastructure\Http\Livewire\ScanReport;
use Modules\Scorecard\Infrastructure\Mail\GradeDroppedMail;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\MonitorModel;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\ScanModel;
use Modules\Scorecard\Infrastructure\Queue\RunScan;
use Modules\Scorecard\Infrastructure\Verification\DomainVerifier;
use Modules\Scorecard\Infrastructure\Verification\TxtRecordLookup;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MonitorTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, mixed> $overrides */
    private function completedScan(array $overrides = []): ScanModel
    {
        return ScanModel::query()->create(array_merge([
            'url' => 'https://example.com',
            'host' => 'example.com',
            'status' => 'completed',
            'grade_letter' => 'A',
            'grade_score' => 95,
            'findings' => [],
            'passed' => ['Env is safe'],
            'completed_at' => now(),
        ], $overrides));
    }

    #[Test]
    public function it_registers_an_unverified_monitor_when_a_visitor_watches(): void
    {
        $scan = $this->completedScan(['grade_letter' => 'B', 'grade_score' => 82]);

        Livewire::test(ScanReport::class, ['scan' => $scan])
            ->set('email', 'dev@example.com')
            ->call('startWatching')
            ->assertHasNoErrors();

        $monitor = MonitorModel::query()->firstOrFail();

        $this->assertSame('example.com', $monitor->host);
        $this->assertSame('dev@example.com', $monitor->email);
        $this->assertSame('B', $monitor->last_grade_letter);
        $this->assertFalse($monitor->isVerified());
        $this->assertNotNull($monitor->verification_token);
    }

    #[Test]
    public function it_verifies_ownership_via_a_meta_tag_and_starts_watching(): void
    {
        $scan = $this->completedScan(['grade_letter' => 'B', 'grade_score' => 82]);

        $component = Livewire::test(ScanReport::class, ['scan' => $scan])
            ->set('email', 'dev@example.com')
            ->call('startWatching');

        $monitor = MonitorModel::query()->firstOrFail();

        $this->mock(TxtRecordLookup::class)->shouldReceive('forHost')->andReturn([]);

        Http::fake([
            'https://example.com/' => Http::response(
                '<html><head><meta name="'.DomainVerifier::NAME.'" content="'.$monitor->verification_token.'"></head></html>', 200
            ),
            '*' => Http::response('', 404),
        ]);

        $component->call('verify')->assertHasNoErrors();

        $this->assertTrue($monitor->fresh()->isVerified());
    }

    #[Test]
    public function it_shows_an_error_when_the_verification_record_is_missing(): void
    {
        $this->mock(TxtRecordLookup::class)->shouldReceive('forHost')->andReturn([]);
        Http::fake(['*' => Http::response('nothing', 404)]);

        $scan = $this->completedScan();

        Livewire::test(ScanReport::class, ['scan' => $scan])
            ->set('email', 'dev@example.com')
            ->call('startWatching')
            ->call('verify')
            ->assertHasErrors('verify');

        $this->assertFalse(MonitorModel::query()->firstOrFail()->isVerified());
    }

    #[Test]
    public function it_emails_the_owner_when_the_grade_drops(): void
    {
        Mail::fake();

        $monitor = MonitorModel::query()->create([
            'url' => 'https://example.com', 'host' => 'example.com', 'email' => 'dev@example.com',
            'last_grade_letter' => 'A', 'last_grade_score' => 95, 'last_scanned_at' => now()->subDay(),
        ]);

        $scan = $this->completedScan(['monitor_id' => $monitor->id, 'grade_letter' => 'F', 'grade_score' => 20]);

        $monitor->evaluateAfterScan($scan);

        Mail::assertSent(GradeDroppedMail::class, fn (GradeDroppedMail $m): bool => $m->hasTo('dev@example.com'));
        $this->assertSame('F', $monitor->fresh()->last_grade_letter);
    }

    #[Test]
    public function it_does_not_email_when_the_grade_improves(): void
    {
        Mail::fake();

        $monitor = MonitorModel::query()->create([
            'url' => 'https://example.com', 'host' => 'example.com', 'email' => 'dev@example.com',
            'last_grade_letter' => 'C', 'last_grade_score' => 70, 'last_scanned_at' => now()->subDay(),
        ]);

        $monitor->evaluateAfterScan($this->completedScan(['monitor_id' => $monitor->id, 'grade_letter' => 'A', 'grade_score' => 95]));

        Mail::assertNothingSent();
        $this->assertSame('A', $monitor->fresh()->last_grade_letter);
    }

    #[Test]
    public function it_does_not_email_on_the_first_scan(): void
    {
        Mail::fake();

        $monitor = MonitorModel::query()->create([
            'url' => 'https://example.com', 'host' => 'example.com', 'email' => 'dev@example.com',
        ]);

        $monitor->evaluateAfterScan($this->completedScan(['monitor_id' => $monitor->id, 'grade_letter' => 'F', 'grade_score' => 10]));

        Mail::assertNothingSent();
        $this->assertSame('F', $monitor->fresh()->last_grade_letter);
    }

    #[Test]
    public function it_queues_a_rescan_only_for_due_verified_monitors(): void
    {
        Queue::fake();

        $due = MonitorModel::query()->create([
            'url' => 'https://due.com', 'host' => 'due.com', 'email' => 'a@example.com',
            'frequency_hours' => 24, 'last_scanned_at' => now()->subHours(30), 'verified_at' => now()->subDays(2),
        ]);

        $notDue = MonitorModel::query()->create([
            'url' => 'https://fresh.com', 'host' => 'fresh.com', 'email' => 'b@example.com',
            'frequency_hours' => 24, 'last_scanned_at' => now()->subHour(), 'verified_at' => now()->subDays(2),
        ]);

        $this->artisan('monitors:rescan')->assertSuccessful();

        Queue::assertPushed(RunScan::class, 1);
        $this->assertSame(1, ScanModel::query()->where('monitor_id', $due->id)->count());
        $this->assertSame(0, ScanModel::query()->where('monitor_id', $notDue->id)->count());
    }

    #[Test]
    public function an_unverified_monitor_is_never_due(): void
    {
        $monitor = MonitorModel::query()->create([
            'url' => 'https://new.com', 'host' => 'new.com', 'email' => 'c@example.com',
            'last_scanned_at' => null, 'verified_at' => null,
        ]);

        $this->assertFalse($monitor->isDue());
    }

    #[Test]
    public function a_verified_never_scanned_monitor_is_due(): void
    {
        $monitor = MonitorModel::query()->create([
            'url' => 'https://new.com', 'host' => 'new.com', 'email' => 'c@example.com',
            'last_scanned_at' => null, 'verified_at' => now(),
        ]);

        $this->assertTrue($monitor->isDue());
    }

    #[Test]
    public function a_recipient_can_unsubscribe_and_resume(): void
    {
        $monitor = MonitorModel::query()->create([
            'url' => 'https://x.com', 'host' => 'x.com', 'email' => 'd@example.com', 'verified_at' => now(),
        ]);

        Livewire::test(ManageMonitor::class, ['monitor' => $monitor])->call('unsubscribe');
        $this->assertFalse($monitor->fresh()->active);
        $this->assertFalse($monitor->fresh()->isDue());

        Livewire::test(ManageMonitor::class, ['monitor' => $monitor])->call('resubscribe');
        $this->assertTrue($monitor->fresh()->active);
    }
}
