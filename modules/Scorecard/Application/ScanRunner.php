<?php

declare(strict_types=1);

namespace Modules\Scorecard\Application;

use Modules\Scorecard\Domain\Check\Check;
use Modules\Scorecard\Domain\ValueObject\Finding;
use Modules\Scorecard\Domain\ValueObject\Grade;
use Modules\Scorecard\Domain\ValueObject\ScanResult;
use Modules\Scorecard\Domain\ValueObject\Target;

/**
 * Runs the registered checks against a target and aggregates the findings into a
 * graded result. The concrete checks are injected (assembled by the module provider),
 * so this use case depends only on the Domain Check contract — never on Infrastructure.
 */
final readonly class ScanRunner
{
    /**
     * @param  list<Check>  $checks
     */
    public function __construct(private array $checks) {}

    public function checkCount(): int
    {
        return count($this->checks);
    }

    /**
     * @param  (callable(int $done, int $total, ?string $currentTitle): void)|null  $onProgress
     */
    public function run(Target $target, ?callable $onProgress = null): ScanResult
    {
        $findings = [];
        $passed = [];
        $total = count($this->checks);

        foreach ($this->checks as $index => $check) {
            // Report the check we're about to run so the UI can show live progress.
            if ($onProgress !== null) {
                $onProgress($index, $total, $check->title());
            }

            $finding = $check->run($target);

            if ($finding instanceof Finding) {
                $findings[] = $finding;
            } else {
                $passed[] = $check->title();
            }
        }

        if ($onProgress !== null) {
            $onProgress($total, $total, null);
        }

        // Worst findings first for the report card.
        usort($findings, fn (Finding $a, Finding $b): int => $a->severity->rank() <=> $b->severity->rank());

        return new ScanResult(
            grade: Grade::fromFindings($findings),
            findings: $findings,
            passed: $passed,
        );
    }
}
