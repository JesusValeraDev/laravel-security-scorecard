<?php

declare(strict_types=1);

namespace Modules\Scorecard\Domain\ValueObject;

/**
 * A single problem detected by a Check. Checks return null when nothing is wrong,
 * so a Finding always represents something the user should act on.
 */
final readonly class Finding
{
    public function __construct(
        public string $checkId,
        public Severity $severity,
        public string $title,
        public string $explanation,
        public string $fix,
        public ?string $evidence = null,
    ) {}
}
