<?php

declare(strict_types=1);

namespace Tests\Unit\Scorecard\Infrastructure;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Facades\Http;
use Modules\Scorecard\Domain\Exception\BlockedHost;
use Modules\Scorecard\Infrastructure\Http\Client\ProbeClient;
use Modules\Scorecard\Infrastructure\Http\Client\PublicHostGuard;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProbeClientTest extends TestCase
{
    private ProbeClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new ProbeClient(new PublicHostGuard);
    }

    #[Test]
    public function it_refuses_to_request_a_private_url(): void
    {
        Http::fake();

        $this->expectException(BlockedHost::class);

        try {
            $this->client->get('http://127.0.0.1/.env');
        } finally {
            Http::assertNothingSent();
        }
    }

    #[Test]
    public function it_refuses_a_redirect_hop_into_the_cloud_metadata_endpoint(): void
    {
        $this->expectException(BlockedHost::class);

        // The hop a public host would try to bounce us through.
        $this->client->assertRedirectAllowed(new Uri('http://169.254.169.254/latest/meta-data/'));
    }

    #[Test]
    public function it_allows_a_redirect_hop_to_a_public_host(): void
    {
        $this->client->assertRedirectAllowed(new Uri('https://example.com/'));

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function it_requests_a_public_url(): void
    {
        Http::fake(['*' => Http::response('hello', 200)]);

        $response = $this->client->get('https://example.com/');

        $this->assertSame('hello', $response->body());
        Http::assertSentCount(1);
    }
}
