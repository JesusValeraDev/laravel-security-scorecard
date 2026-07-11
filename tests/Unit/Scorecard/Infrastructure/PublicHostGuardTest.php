<?php

declare(strict_types=1);

namespace Tests\Unit\Scorecard\Infrastructure;

use Modules\Scorecard\Domain\Exception\BlockedHost;
use Modules\Scorecard\Infrastructure\Http\Client\PublicHostGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PublicHostGuardTest extends TestCase
{
    private PublicHostGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new PublicHostGuard;
    }

    /** @return array<string, array{string}> */
    public static function blockedUrls(): array
    {
        return [
            'loopback ipv4' => ['http://127.0.0.1/'],
            'loopback ipv6' => ['http://[::1]/'],
            'loopback name' => ['http://localhost/'],
            'all interfaces' => ['http://0.0.0.0/'],
            'private class a' => ['http://10.0.0.1/'],
            'private class b' => ['http://172.16.0.1/'],
            'private class c' => ['http://192.168.1.1/'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'internal tld' => ['https://vault.internal/'],
            'local tld' => ['https://printer.local/'],
            'non-http scheme' => ['ftp://example.com/'],
        ];
    }

    #[Test]
    #[DataProvider('blockedUrls')]
    public function it_refuses_hosts_that_are_not_publicly_routable(string $url): void
    {
        $this->assertFalse($this->guard->isPublic($url));
    }

    #[Test]
    #[DataProvider('blockedUrls')]
    public function it_throws_when_asserting_a_blocked_host(string $url): void
    {
        $this->expectException(BlockedHost::class);

        $this->guard->assertPublic($url);
    }

    #[Test]
    public function it_allows_a_public_ip(): void
    {
        $this->assertTrue($this->guard->isPublic('https://93.184.216.34/'));
    }

    #[Test]
    public function it_allows_a_host_that_does_not_resolve(): void
    {
        // Nothing to reach, so there is nothing to protect: the request fails on its own.
        $this->assertTrue($this->guard->isPublic('https://this-host-does-not-exist.invalid/'));
    }
}
