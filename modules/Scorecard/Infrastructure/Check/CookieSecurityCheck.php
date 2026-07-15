<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Check;

use Modules\Scorecard\Domain\Check\Check;
use Modules\Scorecard\Domain\ValueObject\Finding;
use Modules\Scorecard\Domain\ValueObject\Severity;
use Modules\Scorecard\Domain\ValueObject\Target;
use Modules\Scorecard\Infrastructure\Http\Client\ProbeClient;
use Throwable;

/**
 * Inspects the session cookie's security attributes. A session cookie missing Secure
 * or HttpOnly is a real session-hijacking risk. The XSRF-TOKEN cookie is intentionally
 * readable by JavaScript, so it is deliberately ignored here.
 */
final readonly class CookieSecurityCheck implements Check
{
    public function __construct(
        private ProbeClient $client,
    ) {}

    public function id(): string
    {
        return 'cookie-security';
    }

    public function title(): string
    {
        return 'Session cookie is Secure and HttpOnly';
    }

    /** @return list<string> */
    public function probes(): array
    {
        return ['/'];
    }

    public function run(Target $target): ?Finding
    {
        try {
            $response = $this->client->get($target->url());
        } catch (Throwable) {
            return null;
        }

        $setCookies = [];
        foreach ($response->headers() as $name => $values) {
            if (strtolower((string) $name) === 'set-cookie') {
                $setCookies = is_array($values) ? $values : [$values];
                break;
            }
        }

        foreach ($setCookies as $rawCookie) {
            $cookie = (string) $rawCookie;

            // Only judge the session cookie; skip XSRF-TOKEN (must be JS-readable).
            $cookieName = strtolower((string) strtok($cookie, '='));

            if (! str_contains($cookieName, 'session')) {
                continue;
            }

            $lower = strtolower($cookie);
            $missing = [];

            if (! str_contains($lower, 'secure')) {
                $missing[] = 'Secure';
            }

            if (! str_contains($lower, 'httponly')) {
                $missing[] = 'HttpOnly';
            }

            if (! str_contains($lower, 'samesite')) {
                $missing[] = 'SameSite';
            }

            if ($missing === []) {
                return null;
            }

            // Secure/HttpOnly are the serious ones; SameSite alone is a nudge.
            $severity = (in_array('Secure', $missing, true) || in_array('HttpOnly', $missing, true))
                ? Severity::Medium
                : Severity::Low;

            $isPlural = (count($missing) === 1 ? '' : 's');

            return new Finding(
                checkId: $this->id(),
                severity: $severity,
                title: 'Session cookie is missing '.implode(' and ', $missing).' flag'.$isPlural,
                explanation: 'Your session cookie is set without the '.implode(', ', $missing).' attribute'
                .$isPlural.'. Without Secure it can leak over plain HTTP; without HttpOnly a'.
                ' cross-site scripting bug can read it and hijack the session.',
                fix: 'In config/session.php set "secure" => true, "http_only" => true, and a "same_site" of "lax" or'.
                ' "strict" (SESSION_SECURE_COOKIE=true in .env).',
                evidence: 'Session cookie missing: '.implode(', ', $missing),
            );
        }

        return null;
    }
}
