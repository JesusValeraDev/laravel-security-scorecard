<?php

declare(strict_types=1);

namespace Modules\Scorecard\Application;

/**
 * One registered check, described for publication: what it is called and exactly which
 * requests it sends. Generated from the checks themselves so the public list can never
 * drift from what the scanner actually does.
 */
final readonly class CheckManifestEntry
{
    /**
     * @param  list<string>  $probes
     */
    public function __construct(
        public string $id,
        public string $title,
        public array $probes,
    ) {}
}
