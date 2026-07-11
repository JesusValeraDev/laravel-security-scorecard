<?php

declare(strict_types=1);

namespace Tests\Unit\Scorecard\Domain;

use Modules\Scorecard\Domain\ValueObject\Finding;
use Modules\Scorecard\Domain\ValueObject\Grade;
use Modules\Scorecard\Domain\ValueObject\Severity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GradeTest extends TestCase
{
    #[Test]
    public function a_clean_scan_earns_an_a(): void
    {
        $grade = Grade::fromFindings([]);

        $this->assertSame('A', $grade->letter);
        $this->assertSame(100, $grade->score);
        $this->assertTrue($grade->isPassing());
    }

    #[Test]
    public function any_critical_finding_caps_the_grade_at_f(): void
    {
        $grade = Grade::fromFindings([
            $this->finding(Severity::Critical),
        ]);

        $this->assertSame('F', $grade->letter);
        $this->assertFalse($grade->isPassing());
    }

    #[Test]
    public function medium_findings_lower_the_score_without_failing(): void
    {
        $grade = Grade::fromFindings([
            $this->finding(Severity::Medium),
            $this->finding(Severity::Low),
        ]);

        $this->assertSame(84, $grade->score);
        $this->assertSame('B', $grade->letter);
    }

    #[Test]
    public function rank_orders_letters_so_drops_can_be_detected(): void
    {
        $this->assertGreaterThan(Grade::rank('B'), Grade::rank('A'));
        $this->assertGreaterThan(Grade::rank('F'), Grade::rank('D'));
        $this->assertSame(0, Grade::rank('F'));
    }

    private function finding(Severity $severity): Finding
    {
        return new Finding('x', $severity, 't', 'e', 'f');
    }
}
