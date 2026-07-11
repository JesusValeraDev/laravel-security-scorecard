<?php

declare(strict_types=1);

namespace Modules\Scorecard\Domain\ValueObject;

final readonly class ScanResult
{
    /**
     * @param  list<Finding>  $findings  problems detected, worst first
     * @param  list<string>  $passed  titles of checks that passed cleanly
     */
    public function __construct(
        public Grade $grade,
        public array $findings,
        public array $passed,
    ) {}

    public function checksRun(): int
    {
        return count($this->findings) + count($this->passed);
    }
}
