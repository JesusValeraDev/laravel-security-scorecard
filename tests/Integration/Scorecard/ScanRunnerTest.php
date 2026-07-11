<?php

declare(strict_types=1);

namespace Tests\Integration\Scorecard;

use Illuminate\Support\Facades\Http;
use Modules\Scorecard\Application\ScanRunner;
use Modules\Scorecard\Domain\ValueObject\Finding;
use Modules\Scorecard\Domain\ValueObject\ScanResult;
use Modules\Scorecard\Domain\ValueObject\Target;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ScanRunnerTest extends TestCase
{
    private function scan(): ScanResult
    {
        return app(ScanRunner::class)->run(Target::fromUrl('https://example.com'));
    }

    private function findingFor(ScanResult $result, string $checkId): ?Finding
    {
        foreach ($result->findings as $finding) {
            if ($finding->checkId === $checkId) {
                return $finding;
            }
        }

        return null;
    }

    /** @return array<string, string> */
    private function allHeaders(): array
    {
        return [
            'Strict-Transport-Security' => 'max-age=31536000',
            'Content-Security-Policy' => "default-src 'self'",
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Permissions-Policy' => 'geolocation=()',
        ];
    }

    #[Test]
    public function it_flags_an_exposed_env_file_as_critical(): void
    {
        Http::fake([
            '*/.env' => Http::response("APP_ENV=production\nAPP_KEY=base64:abc\nDB_PASSWORD=secret", 200),
            '*' => Http::response('nope', 404),
        ]);

        $result = $this->scan();
        $finding = $this->findingFor($result, 'env-file-exposed');

        $this->assertNotNull($finding);
        $this->assertSame('critical', $finding->severity->value);
        $this->assertSame('F', $result->grade->letter);
    }

    #[Test]
    public function it_does_not_flag_env_without_real_markers(): void
    {
        Http::fake([
            '*/.env' => Http::response('<html>catch-all 200</html>', 200),
            '*' => Http::response('nope', 404),
        ]);

        $this->assertNull($this->findingFor($this->scan(), 'env-file-exposed'));
    }

    #[Test]
    public function a_clean_site_earns_an_a(): void
    {
        Http::fake([
            'http://example.com/' => Http::response('', 301, ['Location' => 'https://example.com/']),
            'https://example.com/' => Http::response('<html>hi</html>', 200, $this->allHeaders()),
            '*' => Http::response('not found', 404),
        ]);

        $result = $this->scan();

        $this->assertSame([], $result->findings);
        $this->assertSame('A', $result->grade->letter);
    }

    #[Test]
    public function it_detects_the_ignition_rce_endpoint_by_a_405(): void
    {
        Http::fake([
            '*/_ignition/execute-solution' => Http::response('Method Not Allowed', 405),
            'https://example.com/' => Http::response('ok', 200, $this->allHeaders()),
            '*' => Http::response('not found', 404),
        ]);

        $this->assertNotNull($this->findingFor($this->scan(), 'ignition-exposed'));
    }

    #[Test]
    public function it_flags_missing_hsts_and_csp_as_at_least_medium(): void
    {
        Http::fake([
            'http://example.com/' => Http::response('', 301, ['Location' => 'https://example.com/']),
            'https://example.com/' => Http::response('<html>hi</html>', 200, [
                'X-Frame-Options' => 'DENY', 'X-Content-Type-Options' => 'nosniff',
            ]),
            '*' => Http::response('not found', 404),
        ]);

        $finding = $this->findingFor($this->scan(), 'security-headers');

        $this->assertNotNull($finding);
        $this->assertContains($finding->severity->value, ['medium', 'high']);
    }

    #[Test]
    public function it_flags_an_exposed_log_file_as_critical(): void
    {
        Http::fake([
            '*/storage/logs/laravel.log' => Http::response(
                "[2024-05-01 12:00:00] production.ERROR: boom\n#0 /var/www/app.php(10)", 200
            ),
            '*' => Http::response('not found', 404),
        ]);

        $finding = $this->findingFor($this->scan(), 'log-file-exposed');

        $this->assertNotNull($finding);
        $this->assertSame('critical', $finding->severity->value);
    }

    #[Test]
    public function it_flags_an_exposed_pulse_dashboard(): void
    {
        Http::fake([
            '*/pulse' => Http::response('<html><title>Pulse</title><script src="/pulse/pulse.js"></script></html>', 200),
            '*' => Http::response('not found', 404),
        ]);

        $this->assertNotNull($this->findingFor($this->scan(), 'pulse-exposed'));
    }

    #[Test]
    public function it_flags_directory_listing(): void
    {
        Http::fake([
            '*/storage/' => Http::response('<h1>Index of /storage</h1>', 200),
            '*' => Http::response('not found', 404),
        ]);

        $this->assertNotNull($this->findingFor($this->scan(), 'directory-listing'));
    }

    #[Test]
    public function it_flags_a_composer_lock_file(): void
    {
        Http::fake([
            '*/composer.lock' => Http::response('{"_readme":["x"],"content-hash":"abc","packages":[]}', 200),
            '*' => Http::response('not found', 404),
        ]);

        $finding = $this->findingFor($this->scan(), 'composer-lock-exposed');

        $this->assertNotNull($finding);
        $this->assertSame('medium', $finding->severity->value);
    }

    #[Test]
    public function it_flags_plain_http_not_redirected_to_https(): void
    {
        Http::fake([
            'http://example.com/' => Http::response('<html>served over http</html>', 200),
            '*' => Http::response('not found', 404),
        ]);

        $this->assertNotNull($this->findingFor($this->scan(), 'https-redirect'));
    }
}
