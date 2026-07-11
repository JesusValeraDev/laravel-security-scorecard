<?php

declare(strict_types=1);

namespace Tests\Feature\Scorecard;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\Scorecard\Infrastructure\Http\Livewire\ScanForm;
use Modules\Scorecard\Infrastructure\Http\Livewire\ScanReport;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\ScanModel;
use Modules\Scorecard\Infrastructure\Queue\RunScan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ScanFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_runs_a_scan_from_the_form_and_shows_the_report_card(): void
    {
        Http::fake([
            '*/.env' => Http::response("APP_ENV=production\nAPP_KEY=base64:x\nDB_PASSWORD=y", 200),
            '*' => Http::response('nope', 404),
        ]);

        Livewire::test(ScanForm::class)
            ->set('url', 'exposed-app.com')
            ->call('scan')
            ->assertHasNoErrors()
            ->assertRedirect();

        $scan = ScanModel::query()->firstOrFail();

        $this->assertSame('completed', $scan->status);
        $this->assertSame('F', $scan->grade_letter);
        $this->assertSame('exposed-app.com', $scan->host);

        $this->get(route('report', $scan))
            ->assertOk()
            ->assertSee('Scorecard for')
            ->assertSee('exposed-app.com')
            ->assertSee('.env file is publicly downloadable');
    }

    #[Test]
    public function it_shows_a_live_progress_view_while_running(): void
    {
        $scan = ScanModel::query()->create([
            'url' => 'https://example.com',
            'host' => 'example.com',
            'status' => 'scanning',
            'checks_total' => 13,
            'checks_done' => 5,
            'current_check' => 'Telescope is not publicly exposed',
        ]);

        Livewire::test(ScanReport::class, ['scan' => $scan])
            ->assertSee('Scanning example.com')
            ->assertSee('Telescope is not publicly exposed')
            ->assertSee('5 of 13 checks')
            ->assertDontSee('Scorecard for');

        $this->assertSame(38, $scan->progressPercent());
    }

    #[Test]
    public function it_queues_the_scan_rather_than_running_it_inline(): void
    {
        Queue::fake();

        Livewire::test(ScanForm::class)
            ->set('url', 'example.com')
            ->call('scan')
            ->assertRedirect();

        $scan = ScanModel::query()->firstOrFail();

        $this->assertSame('pending', $scan->status);
        $this->assertSame(13, $scan->checks_total);

        Queue::assertPushed(RunScan::class);
    }

    #[Test]
    public function it_rejects_a_private_host_without_creating_a_scan(): void
    {
        Livewire::test(ScanForm::class)
            ->set('url', 'http://localhost')
            ->call('scan')
            ->assertHasErrors('url');

        $this->assertSame(0, ScanModel::query()->count());
    }
}
