<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Http\Client;

use InvalidArgumentException;
use Modules\Scorecard\Domain\Exception\BlockedHost;
use Modules\Scorecard\Domain\ValueObject\Target;

/**
 * Decides whether a URL is safe for the scanner to request.
 *
 * Target::isPubliclyRoutable() only inspects the host *string*, which is not enough:
 * a perfectly public-looking name can resolve to 127.0.0.1 or to 169.254.169.254 (the
 * cloud metadata endpoint). So we also resolve the name and reject it if any address it
 * points at is private or reserved.
 *
 * A host that does not resolve at all is allowed through — there is nothing to reach, and
 * the request will fail on its own.
 */
final class PublicHostGuard
{
    /** @var array<string, bool> Resolution is cached so one scan does not re-query DNS 13 times. */
    private array $cache = [];

    public function assertPublic(string $url): void
    {
        if (! $this->isPublic($url)) {
            throw BlockedHost::forUrl($url);
        }
    }

    public function isPublic(string $url): bool
    {
        return $this->cache[$url] ??= $this->evaluate($url);
    }

    private function evaluate(string $url): bool
    {
        try {
            $target = Target::fromUrl($url);
        } catch (InvalidArgumentException) {
            return false;
        }

        if (! $target->isPubliclyRoutable()) {
            return false;
        }

        return array_all($this->resolve($target->host), fn (string $ip): bool => $this->isPublicIp($ip));
    }

    /**
     * @return list<string> Empty when the host does not resolve.
     */
    private function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if ($records === false) {
            return [];
        }

        $ips = [];

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if (is_string($ip) && $ip !== '') {
                $ips[] = $ip;
            }
        }

        return $ips;
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
