<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Http\Client;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Scorecard\Domain\Exception\BlockedHost;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * The single door every passive probe goes through.
 *
 * Checks must not call Http:: directly — routing them all through here means the SSRF
 * guard, the redirect cap and the timeout are decided in exactly one place.
 */
final readonly class ProbeClient
{
    private const int DEFAULT_TIMEOUT = 8;

    private const int MAX_REDIRECTS = 5;

    public function __construct(private PublicHostGuard $guard) {}

    /**
     * Follows redirects, because the checks that read headers, cookies and version banners
     * want the response a visitor actually lands on.
     *
     * @throws BlockedHost
     */
    public function get(string $url, int $timeout = self::DEFAULT_TIMEOUT): Response
    {
        $this->guard->assertPublic($url);

        return Http::timeout($timeout)
            ->withOptions([
                'allow_redirects' => [
                    'max' => self::MAX_REDIRECTS,
                    'strict' => true,
                    'referer' => false,
                    'protocols' => ['http', 'https'],
                    'on_redirect' => function (
                        RequestInterface $request,
                        ResponseInterface $response,
                        UriInterface $uri
                    ): void {
                        $this->assertRedirectAllowed($uri);
                    },
                ],
            ])
            ->get($url);
    }

    /**
     * Guarding the submitted URL is not enough: a public host can 302 us into the private
     * network, so every hop is re-checked before we follow it.
     *
     * @throws BlockedHost
     */
    public function assertRedirectAllowed(UriInterface $uri): void
    {
        $this->guard->assertPublic((string) $uri);
    }

    /**
     * Leaves the redirect unfollowed, for the check whose job is to observe the redirect
     * itself (plain HTTP → HTTPS).
     *
     * @throws BlockedHost
     */
    public function getWithoutRedirecting(string $url, int $timeout = self::DEFAULT_TIMEOUT): Response
    {
        $this->guard->assertPublic($url);

        return Http::timeout($timeout)->withoutRedirecting()->get($url);
    }
}
