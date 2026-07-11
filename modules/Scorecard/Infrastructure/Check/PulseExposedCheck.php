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
 * Detects an unauthenticated Laravel Pulse dashboard, which exposes application
 * performance data — slow queries, exceptions, slow requests, and active users.
 */
final class PulseExposedCheck implements Check
{
    public function id(): string
    {
        return 'pulse-exposed';
    }

    public function title(): string
    {
        return 'Pulse is not publicly exposed';
    }

    public function run(Target $target): ?Finding
    {
        try {
            $response = Http::timeout(8)->get($target->url('pulse'));
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $body = $response->body();

        // Pulse serves a Livewire dashboard with these unmistakable markers.
        if (! str_contains($body, 'Pulse')) {
            return null;
        }

        if (! str_contains($body, 'pulse/pulse.js')
            && ! str_contains($body, 'laravel-pulse')
            && ! str_contains($body, 'pulse/dashboard')) {
            return null;
        }

        return new Finding(
            checkId: $this->id(),
            severity: Severity::High,
            title: 'Pulse is reachable without authentication',
            explanation: 'The Pulse dashboard is open at '.$target->url('pulse').'. It reveals '
                .'application performance internals — slow queries and requests, exceptions, '
                .'queue throughput, and currently active users.',
            fix: 'Restrict Pulse with the "viewPulse" gate in your PulseServiceProvider so '
                .'only authorized users can reach /pulse.',
            evidence: 'Pulse dashboard served at /pulse.',
        );
    }
}
