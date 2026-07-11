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

        // Default to https when the user omits the scheme.
        if (! preg_match('#^https?://#i', $input)) {
            $input = 'https://'.$input;
        }

        $parts = parse_url($input);

        if ($parts === false || empty($parts['host'])) {
            throw new InvalidArgumentException('That does not look like a valid URL.');
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only http and https URLs can be scanned.');
        }

        return new self($scheme, strtolower($parts['host']), $parts['port'] ?? null);
    }

    public function origin(): string
    {
        $origin = $this->scheme.'://'.$this->host;

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
