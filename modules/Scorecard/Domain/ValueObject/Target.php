<?php

declare(strict_types=1);

namespace Modules\Scorecard\Domain\ValueObject;

use InvalidArgumentException;

/**
 * The site under inspection. Normalizes the user-supplied URL to a scheme + host
 * origin and builds absolute URLs for the paths that checks probe.
 */
final readonly class Target
{
    public function __construct(
        public string $scheme,
        public string $host,
        public ?int $port = null,
    ) {}

    public static function fromUrl(string $input): self
    {
        $input = trim($input);

        if ($input === '') {
            throw new InvalidArgumentException('URL is required.');
        }

        // Reject a foreign scheme up front. Prepending https:// to "ftp://host" would
        // otherwise smuggle it through as the host "ftp".
        if (preg_match('#^([a-z][a-z0-9+.-]*)://#i', $input, $matches) === 1) {
            if (! in_array(strtolower($matches[1]), ['http', 'https'], true)) {
                throw new InvalidArgumentException('Only http and https URLs can be scanned.');
            }
        } else {
            // Default to https when the user omits the scheme.
            $input = 'https://'.$input;
        }

        $parts = parse_url($input);

        if ($parts === false || empty($parts['host'])) {
            throw new InvalidArgumentException('That does not look like a valid URL.');
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');

        // parse_url keeps IPv6 literals bracketed ("[::1]"); store the bare address so
        // that IP-based guards can actually match it.
        $host = strtolower(trim($parts['host'], '[]'));

        if (! self::isHostname($host) && filter_var($host, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException('Enter a full domain, like myapp.com.');
        }

        return new self($scheme, $host, $parts['port'] ?? null);
    }

    /**
     * A scannable name needs at least two labels and an alphabetic TLD. A bare word like
     * "test123" is not a site we could ever reach, so it must never become a scan.
     */
    private static function isHostname(string $host): bool
    {
        if (strlen($host) > 253) {
            return false;
        }

        return preg_match(
            '/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/',
            $host
        ) === 1;
    }

    public function origin(): string
    {
        // Re-wrap IPv6 literals, which a URL requires to be bracketed.
        $host = str_contains($this->host, ':') ? '['.$this->host.']' : $this->host;

        $origin = $this->scheme.'://'.$host;

        if ($this->port !== null) {
            $origin .= ':'.$this->port;
        }

        return $origin;
    }

    public function url(string $path = '/'): string
    {
        return $this->origin().'/'.ltrim($path, '/');
    }

    /**
     * Guards against scanning private/loopback hosts (SSRF hygiene). A weekend-scope
     * check; a production build would also resolve DNS and block private IP ranges.
     */
    public function isPubliclyRoutable(): bool
    {
        $host = $this->host;

        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) !== false;
        }

        // Block obvious internal TLDs.
        return ! preg_match('/\.(local|internal|test|localhost)$/i', $host);
    }
}
