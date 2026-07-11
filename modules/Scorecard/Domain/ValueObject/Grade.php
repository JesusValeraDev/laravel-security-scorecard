<?php

declare(strict_types=1);

namespace Modules\Scorecard\Domain\ValueObject;

/**
 * A letter grade (A–F) derived from the severity of the findings. Any single
 * critical finding caps the grade at F — leaking secrets is never a "B".
 */
final readonly class Grade
{
    public function __construct(
        public string $letter,
        public int $score,
    ) {}

    /**
     * @param  list<Finding>  $findings
     */
    public static function fromFindings(array $findings): self
    {
        $score = 100;
        $hasCritical = false;

        foreach ($findings as $finding) {
            $score -= $finding->severity->penalty();

            if ($finding->severity === Severity::Critical) {
                $hasCritical = true;
            }
        }

        $score = max(0, min(100, $score));

        $letter = match (true) {
            $hasCritical => 'F',
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 55 => 'D',
            default => 'F',
        };

        return new self($letter, $score);
    }

    public function isPassing(): bool
    {
        return in_array($this->letter, ['A', 'B'], true);
    }

    /**
     * Higher is better. Used to decide whether a grade has genuinely dropped
     * (a worse letter), rather than alerting on trivial point changes within a letter.
     */
    public static function rank(string $letter): int
    {
        return match ($letter) {
            'A' => 5,
            'B' => 4,
            'C' => 3,
            'D' => 2,
            default => 0, // F
        };
    }
}
