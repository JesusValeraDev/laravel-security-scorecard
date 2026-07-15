<?php

declare(strict_types=1);

namespace Tests\Feature\Scorecard;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Scorecard\Infrastructure\Http\Livewire\ScanForm;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ScanFlowTest extends TestCase
{
    #[Test]
    public function it_refuses_an_empty_submission(): void
    {
        // The button is disabled while the field is empty, but the server must not rely on that.
        Http::fake();

        Livewire::test(ScanForm::class)
            ->set('url', '   ')
            ->call('scan')
            ->assertHasErrors('url')
            ->assertSet('scanned', false);

        Http::assertNothingSent();
    }

    #[Test]
    public function it_refuses_a_bare_word_that_is_not_a_url(): void
    {
        Http::fake();

        Livewire::test(ScanForm::class)
            ->set('url', 'test123')
            ->call('scan')
            ->assertHasErrors('url')
            ->assertSet('scanned', false);

        Http::assertNothingSent();
    }

    #[Test]
    public function it_rejects_a_private_host_without_scanning(): void
    {
        Http::fake();

        Livewire::test(ScanForm::class)
            ->set('url', 'http://localhost')
            ->call('scan')
            ->assertHasErrors('url')
            ->assertSet('scanned', false);

        Http::assertNothingSent();
    }

    #[Test]
    public function it_fails_the_scan_rather_than_grading_a_site_that_never_answered(): void
    {
        // Every check would swallow the connection error and report nothing, which used to
        // hand an unreachable site a clean A.
        Http::fake(fn () => throw new ConnectionException('Could not resolve host.'));

        Livewire::test(ScanForm::class)
            ->set('url', 'offline-app.com')
            ->call('scan')
            ->assertHasNoErrors()
            ->assertSet('scanned', false)
            ->assertSee('Scan failed')
            ->assertSee('no response');
    }

    #[Test]
    public function it_runs_a_scan_from_the_form_and_shows_the_report_card_inline(): void
    {
        Http::fake([
            '*/.env' => Http::response("APP_ENV=production\nAPP_KEY=base64:x\nDB_PASSWORD=y", 200),
            '*' => Http::response('nope', 404),
        ]);

        Livewire::test(ScanForm::class)
            ->set('url', 'exposed-app.com')
            ->call('scan')
            ->assertHasNoErrors()
            ->assertSet('scanned', true)
            ->assertSet('gradeLetter', 'F')
            ->assertSet('host', 'exposed-app.com')
            ->assertSee('What we found')
            ->assertSee('exposed-app.com')
            ->assertSee('.env file is publicly downloadable');
    }
}
