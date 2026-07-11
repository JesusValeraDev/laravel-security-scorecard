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
 * Detects an unauthenticated Laravel Horizon dashboard, which exposes queue internals
 * and lets anyone retry or delete jobs.
 */
final class HorizonExposedCheck implements Check
{
    public function id(): string
    {
        return 'horizon-exposed';
    }

    public function title(): string
    {
        return 'Horizon is not publicly exposed';
    }

    public function run(Target $target): ?Finding
    {
        try {
            $response = Http::timeout(8)->get($target->url('horizon/api/stats'));
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        // The stats API returns JSON with these keys when Horizon is open.
        $json = $response->json();

        if (! is_array($json)) {
            return null;
        }

        $expected = ['jobsPerMinute', 'processes', 'queueWithMaxRuntime', 'periods'];
        $present = array_filter($expected, fn (string $k): bool => array_key_exists($k, $json));

        if (count($present) < 2) {
            return null;
        }

        return new Finding(
            checkId: $this->id(),
            severity: Severity::High,
            title: 'Horizon is reachable without authentication',
            explanation: 'The Horizon dashboard is open at '.$target->url('horizon')
                .'. It exposes your queue workload and lets anyone pause workers or retry '
                .'and delete jobs.',
            fix: 'Lock Horizon down with the HorizonServiceProvider gate (the "viewHorizon" '
                .'gate), so only authorized users can reach /horizon.',
            evidence: 'Horizon stats API responded with live queue metrics.',
        );
    }
}
