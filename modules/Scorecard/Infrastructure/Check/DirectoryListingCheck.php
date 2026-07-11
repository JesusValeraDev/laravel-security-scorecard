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
 * Detects web-server directory listing (autoindex), which exposes the file layout of
 * a directory. Confirmed by the unmistakable "Index of /..." autoindex signature.
 */
final class DirectoryListingCheck implements Check
{
    private const array PROBE_PATHS = ['storage', 'vendor', 'assets', 'uploads'];

    public function id(): string
    {
        return 'directory-listing';
    }

    public function title(): string
    {
        return 'Directory listing is disabled';
    }

    public function run(Target $target): ?Finding
    {
        foreach (self::PROBE_PATHS as $path) {
            try {
                $response = Http::timeout(6)->get($target->url($path.'/'));
            } catch (Throwable) {
                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $body = $response->body();

            // Apache and nginx both emit an "Index of /path" heading for autoindex.
            if (! preg_match('#Index of /'.preg_quote($path, '#').'#i', $body)) {
                continue;
            }

            return new Finding(
                checkId: $this->id(),
                severity: Severity::Low,
                title: 'Directory listing is enabled',
                explanation: 'The web server returns a browsable file listing at '
                    .$target->url($path.'/').'. This exposes your directory structure and '
                    .'can reveal files that were never meant to be linked publicly.',
                fix: 'Disable autoindex on the web server (Options -Indexes in Apache, or '
                    .'remove autoindex on in nginx), and serve only the public/ directory.',
                evidence: 'Autoindex page found at /'.$path.'/.',
            );
        }

        return null;
    }
}
