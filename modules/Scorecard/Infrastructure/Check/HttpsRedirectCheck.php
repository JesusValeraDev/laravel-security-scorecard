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
 * Checks that the plain-http site redirects to https. If http is served directly
 * (200) or redirects to another http location, credentials and cookies can travel
 * in cleartext.
 */
final class HttpsRedirectCheck implements Check
{
    public function id(): string
    {
        return 'https-redirect';
    }

    public function title(): string
    {
        return 'Plain HTTP redirects to HTTPS';
    }

    public function run(Target $target): ?Finding
    {
        $httpUrl = 'http://'.$target->host.($target->port ? ':'.$target->port : '').'/';

        try {
            $response = Http::timeout(8)->withoutRedirecting()->get($httpUrl);
        } catch (Throwable) {
            // No http listener at all is fine — nothing to downgrade to.
            return null;
        }

        $status = $response->status();
        $location = (string) $response->header('Location');

        // A redirect to an https location is exactly what we want.
        if ($status >= 300 && $status < 400 && str_starts_with(strtolower($location), 'https://')) {
            return null;
        }

        // A redirect to a relative/https-implied location — treat scheme-relative as ok
        // only when it clearly upgrades; otherwise flag.
        if ($status >= 300 && $status < 400 && $location === '') {
            return null;
        }

        return new Finding(
            checkId: $this->id(),
            severity: Severity::Medium,
            title: 'HTTP is not redirected to HTTPS',
            explanation: 'Requests to '.$httpUrl.' are served over plain HTTP instead of being '
                .'redirected to HTTPS. Anyone on the network path can read or tamper with '
                .'traffic, including session cookies and login credentials.',
            fix: 'Force HTTPS at the web server or load balancer (a 301 redirect from http to '
                .'https), and pair it with an HSTS header so browsers refuse to downgrade.',
            evidence: 'http:// responded '.$status.($location !== '' ? ' → '.$location : ''),
        );
    }
}
