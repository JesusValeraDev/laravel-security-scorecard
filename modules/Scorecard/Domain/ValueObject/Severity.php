<?php

declare(strict_types=1);

namespace Modules\Scorecard\Domain\ValueObject;

enum Severity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    /**
     * Points deducted from a perfect score of 100 when this finding is present.
     */
    public function penalty(): int
    {
        return match ($this) {
            self::Critical => 55,
            self::High => 30,
            self::Medium => 12,
            self::Low => 4,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function rank(): int
    {
        return match ($this) {
            self::Critical => 0,
            self::High => 1,
            self::Medium => 2,
            self::Low => 3,
        };
    }
}
