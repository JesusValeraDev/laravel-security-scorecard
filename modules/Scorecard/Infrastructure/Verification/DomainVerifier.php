<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Verification;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Proves that whoever registered a monitor controls the domain, before we start
 * scanning it on a schedule. Accepts any one of three proofs: a DNS TXT record, a
 * homepage meta tag, or a well-known file.
 */
class DomainVerifier
{
    public const NAME = 'laravel-scorecard-site-verification';

    public function __construct(private readonly TxtRecordLookup $txt) {}

    /** The value the owner must publish, e.g. "laravel-scorecard-site-verification=abc123". */
    public function expectedRecord(string $token): string
    {
        return self::NAME.'='.$token;
    }

    public function verify(string $host, string $url, string $token): bool
    {
        if ($this->viaDns($host, $token)) {
            return true;
        }
        if ($this->viaMetaTag($url, $token)) {
            return true;
        }

        return $this->viaWellKnownFile($url, $token);
    }

    private function viaDns(string $host, string $token): bool
    {
        $expected = $this->expectedRecord($token);

        return array_any($this->txt->forHost($host), fn (string $record): bool => trim($record) === $expected);
    }

    private function viaMetaTag(string $url, string $token): bool
    {
        try {
            $body = Http::timeout(8)->get(rtrim($url, '/').'/')->body();
        } catch (Throwable) {
            return false;
        }

        $pattern = '/<meta\s+name=["\']'.preg_quote(self::NAME, '/')
            .'["\']\s+content=["\']'.preg_quote($token, '/').'["\']/i';

        return (bool) preg_match($pattern, $body);
    }

    private function viaWellKnownFile(string $url, string $token): bool
    {
        try {
            $response = Http::timeout(8)->get(rtrim($url, '/').'/.well-known/'.self::NAME.'.txt');
        } catch (Throwable) {
            return false;
        }

        return $response->successful() && trim($response->body()) === $token;
    }
}
