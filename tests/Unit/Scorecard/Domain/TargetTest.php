<?php

declare(strict_types=1);

namespace Tests\Unit\Scorecard\Domain;

use InvalidArgumentException;
use Modules\Scorecard\Domain\ValueObject\Target;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TargetTest extends TestCase
{
    #[Test]
    public function it_defaults_to_https_when_no_scheme_is_given(): void
    {
        $target = Target::fromUrl('example.com');

        $this->assertSame('https', $target->scheme);
        $this->assertSame('example.com', $target->host);
        $this->assertSame('https://example.com', $target->origin());
    }

    #[Test]
    public function it_builds_absolute_urls_for_probe_paths(): void
    {
        $target = Target::fromUrl('https://example.com');

        $this->assertSame('https://example.com/.env', $target->url('.env'));
        $this->assertSame('https://example.com/telescope/requests', $target->url('telescope/requests'));
    }

    #[Test]
    public function it_rejects_an_empty_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Target::fromUrl('   ');
    }

    /** @return array<string, array{string}> */
    public static function invalidUrls(): array
    {
        return [
            'bare word' => ['test123'],
            'single label' => ['localhost'],
            'no tld' => ['myapp.'],
            'numeric tld' => ['myapp.123'],
            'spaces' => ['not a url'],
            'foreign scheme' => ['ftp://example.com'],
        ];
    }

    #[Test]
    #[DataProvider('invalidUrls')]
    public function it_rejects_anything_that_is_not_a_reachable_url(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        Target::fromUrl($input);
    }

    #[Test]
    public function it_accepts_domains_and_public_ips(): void
    {
        $this->assertSame('example.com', Target::fromUrl('example.com')->host);
        $this->assertSame('api.staging.example.co.uk', Target::fromUrl('https://api.staging.example.co.uk/path')->host);
        $this->assertSame('93.184.216.34', Target::fromUrl('http://93.184.216.34')->host);
    }

    #[Test]
    public function it_blocks_local_and_private_hosts(): void
    {
        $this->assertFalse(Target::fromUrl('http://127.0.0.1')->isPubliclyRoutable());
        $this->assertFalse(Target::fromUrl('http://[::1]')->isPubliclyRoutable());
        $this->assertFalse(Target::fromUrl('https://app.test')->isPubliclyRoutable());
        $this->assertTrue(Target::fromUrl('https://example.com')->isPubliclyRoutable());
    }
}
