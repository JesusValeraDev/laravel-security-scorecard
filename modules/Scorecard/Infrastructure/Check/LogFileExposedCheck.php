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
 * Detects a publicly readable Laravel log file, which routinely contains stack traces,
 * SQL, and leaked secrets/PII. Confirmed by the distinctive log line signature.
 */
final class LogFileExposedCheck implements Check
{
    public function id(): string
    {
        return 'log-file-exposed';
    }

    public function title(): string
    {
        return 'Application log is not public';
    }

    public function run(Target $target): ?Finding
    {
        try {
            $response = Http::timeout(8)->get($target->url('storage/logs/laravel.log'));
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $body = $response->body();

        // Laravel log lines look like: [2024-01-01 12:00:00] production.ERROR: ...
        if (! preg_match('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]\s+\w+\.(ERROR|INFO|WARNING|DEBUG|CRITICAL)/', $body)) {
            return null;
        }

        return new Finding(
            checkId: $this->id(),
            severity: Severity::Critical,
            title: 'Your Laravel log file is publicly readable',
            explanation: 'The log at '.$target->url('storage/logs/laravel.log').' is served to '
                .'anyone. Laravel logs commonly contain stack traces, SQL queries, request '
                .'payloads, tokens, and personal data — a direct information leak.',
            fix: 'Make sure the web root is the public/ directory so storage/ is never served, '
                .'and block access to it at the web server. Rotate any secrets that may have '
                .'appeared in the logs.',
            evidence: 'Response matched Laravel log line format.',
        );
    }
}
