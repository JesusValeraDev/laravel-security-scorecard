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
 * Detects an unauthenticated Laravel Telescope dashboard, which exposes every
 * request, query, job, and payload flowing through the app.
 */
final class TelescopeExposedCheck implements Check
{
    public function id(): string
    {
        return 'telescope-exposed';
    }

    public function title(): string
    {
        return 'Telescope is not publicly exposed';
    }

    public function run(Target $target): ?Finding
    {
        try {
            $response = Http::timeout(8)->get($target->url('telescope/requests'));
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $body = $response->body();

        // Telescope serves a Vue SPA shell with these unmistakable markers.
        if (! str_contains($body, 'Telescope') || ! str_contains($body, 'telescope')) {
            return null;
        }

        if (! str_contains($body, 'id="telescope')
            && ! str_contains($body, 'telescope/app.js')) {
            return null;
        }

        return new Finding(
            checkId: $this->id(),
            severity: Severity::High,
            title: 'Telescope is reachable without authentication',
            explanation: 'The Telescope dashboard is open at '.$target->url('telescope')
                .'. It records every request, database query, job, and mail — often '
                .'including credentials and personal data in the payloads.',
            fix: 'Restrict Telescope with the TelescopeServiceProvider gate, or disable it in '
                .'production (TELESCOPE_ENABLED=false). Never leave it open to the internet.',
            evidence: 'Telescope UI served at telescope/requests.',
        );
    }
}
