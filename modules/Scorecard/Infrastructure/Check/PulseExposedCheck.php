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
 * Detects an unauthenticated Laravel Pulse dashboard, which exposes application
 * performance data — slow queries, exceptions, slow requests, and active users.
 */
final readonly class PulseExposedCheck implements Check
{
    public function __construct(private ProbeClient $client) {}

    public function id(): string
    {
        return 'pulse-exposed';
    }

    public function title(): string
    {
        return 'Pulse is not publicly exposed';
    }

    /** @return list<string> */
    public function probes(): array
    {
        return ['/pulse'];
    }

    public function run(Target $target): ?Finding
    {
        try {
            $response = $this->client->get($target->url('pulse'));
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
