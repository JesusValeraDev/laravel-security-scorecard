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
 * Detects a publicly readable .env file. Confirmed by matching real Laravel env
 * keys in the response body — a 200 alone is not enough (catch-all routes exist).
 */
final class EnvFileExposedCheck implements Check
{
    public function id(): string
    {
        return 'env-file-exposed';
    }

    public function title(): string
    {
        return 'Environment file (.env) is not public';
    }

    public function run(Target $target): ?Finding
    {
        try {
            $response = Http::timeout(8)->get($target->url('.env'));
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $body = $response->body();

        // Require concrete evidence: env-style keys that a real .env would contain.
        $markers = ['APP_KEY=', 'APP_ENV=', 'DB_PASSWORD=', 'DB_CONNECTION='];
        $hits = array_filter($markers, fn (string $m): bool => str_contains($body, $m));

        if (count($hits) < 2) {
            return null;
        }

        return new Finding(
            checkId: $this->id(),
            severity: Severity::Critical,
            title: 'Your .env file is publicly downloadable',
            explanation: 'Anyone can read '.$target->url('.env').', which exposes your '
                .'app key, database credentials, and third-party API secrets. This is a '
                .'full compromise of your application.',
            fix: 'Ensure your web root points at the public/ directory, never the project '
                .'root. Rotate APP_KEY and every credential in the file immediately — assume '
                .'they are already leaked.',
            evidence: 'Response contained: '.implode(', ', $hits),
        );
    }
}
