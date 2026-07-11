<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Check;

use Illuminate\Support\Facades\Http;
use Modules\Scorecard\Domain\Check\Check;
use Modules\Scorecard\Domain\ValueObject\Finding;
use Modules\Scorecard\Domain\ValueObject\Severity;
use Modules\Scorecard\Domain\ValueObject\Target;
use Throwable;

/**
 * Flags Server / X-Powered-By headers that disclose exact software versions, which
 * hands attackers a shortlist of version-specific CVEs to try.
 */
final class ServerVersionDisclosureCheck implements Check
{
    public function id(): string
    {
        return 'server-version-disclosure';
    }

    public function title(): string
    {
        return 'Server software versions are not disclosed';
    }

    public function run(Target $target): ?Finding
    {
        try {
            $response = Http::timeout(8)->get($target->url('/'));
        } catch (Throwable) {
            return null;
        }

        $disclosed = [];

        $server = (string) $response->header('Server');
        // A bare "nginx" or "cloudflare" is fine; a version number (digits) is the leak.
        if ($server !== '' && preg_match('/\d+\.\d+/', $server)) {
            $disclosed[] = 'Server: '.$server;
        }

        $poweredBy = (string) $response->header('X-Powered-By');
        if ($poweredBy !== '') {
            $disclosed[] = 'X-Powered-By: '.$poweredBy;
        }

        if ($disclosed === []) {
            return null;
        }

        return new Finding(
            checkId: $this->id(),
            severity: Severity::Low,
            title: 'Server software version is disclosed in headers',
            explanation: 'Response headers reveal specific software versions ('
                .implode('; ', $disclosed).'). Attackers use this to look up known '
                .'vulnerabilities for that exact version.',
            fix: 'Suppress version banners: set server_tokens off (nginx) or ServerTokens '
                .'Prod (Apache), and remove X-Powered-By (expose_php = Off, or strip it in '
                .'middleware).',
            evidence: implode('; ', $disclosed),
        );
    }
}
