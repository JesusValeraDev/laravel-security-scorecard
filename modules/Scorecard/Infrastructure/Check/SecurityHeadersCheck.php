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
 * Flags missing HTTP security headers on the homepage. These are hardening measures,
 * not active leaks, so severity is capped — but the important ones (HSTS, CSP) are
 * weighted heavily, matching how tools like securityheaders.com grade a site.
 */
final readonly class SecurityHeadersCheck implements Check
{
    public function __construct(
        private ProbeClient $client,
    ) {}

    /**
     * Header name (lowercase) => [display label, weight]. Higher weight = bigger risk.
     *
     * @var array<string, array{0: string, 1: int}>
     */
    private const array HEADERS = [
        'strict-transport-security' => ['Strict-Transport-Security (HSTS)', 3],
        'content-security-policy' => ['Content-Security-Policy', 3],
        'x-frame-options' => ['X-Frame-Options', 2],
        'x-content-type-options' => ['X-Content-Type-Options', 1],
        'referrer-policy' => ['Referrer-Policy', 1],
        'permissions-policy' => ['Permissions-Policy', 1],
    ];

    public function id(): string
    {
        return 'security-headers';
    }

    public function title(): string
    {
        return 'Recommended security headers are present';
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

        if (! $response->successful()) {
            return null;
        }

        $missing = [];
        $weight = 0;

        foreach (self::HEADERS as $header => [$label, $headerWeight]) {
            if ($response->header($header) === '') {
                $missing[] = $label;
                $weight += $headerWeight;
            }
        }

        if ($missing === []) {
            return null;
        }

        // Weight the finding by risk: missing HSTS + CSP alone already lands at Medium.
        $severity = match (true) {
            $weight >= 6 => Severity::High,
            $weight >= 3 => Severity::Medium,
            default => Severity::Low,
        };

        return new Finding(
            checkId: $this->id(),
            severity: $severity,
            title: count($missing).' recommended security header'.(count($missing) === 1 ? '' : 's').' missing',
            explanation: 'Your homepage is missing: '.implode(', ', $missing).'. These headers defend against'.
            ' protocol downgrade (HSTS), cross-site scripting (CSP), clickjacking (X-Frame-Options), and MIME sniffing. '
            .'Their absence is how header graders like securityheaders.com dock points.',
            fix: 'Add the missing headers via middleware (or your web server config). A sensible baseline: HSTS with a'
            .' long max-age, a Content-Security-Policy, X-Frame-Options: DENY, X-Content-Type-Options: nosniff, a'
            .' Referrer-Policy, and a Permissions-Policy.',
            evidence: 'Missing: '.implode(', ', $missing),
        );
    }
}
