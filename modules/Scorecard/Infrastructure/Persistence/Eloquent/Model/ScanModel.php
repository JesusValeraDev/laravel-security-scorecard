<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Scorecard\Domain\ValueObject\Finding;
use Modules\Scorecard\Domain\ValueObject\Grade;
use Modules\Scorecard\Domain\ValueObject\ScanResult;

/**
 * @property string $token
 * @property string $url
 * @property string $host
 * @property string $status
 * @property ?string $grade_letter
 * @property ?int $grade_score
 * @property ?array $findings
 * @property ?array $passed
 */
class ScanModel extends Model
{
    /** @use HasUlids<ScanModel> */
    use HasUlids;

    #[\Override]
    protected $table = 'scans';

    #[\Override]
    protected $guarded = [];

    #[\Override]
    public function uniqueIds(): array
    {
        return ['token'];
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'token';
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'findings' => 'array',
            'passed' => 'array',
            'grade_score' => 'integer',
            'checks_total' => 'integer',
            'checks_done' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<MonitorModel, ScanModel> */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(MonitorModel::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isScanning(): bool
    {
        return $this->status === 'scanning';
    }

    /** Queued or actively running — i.e. not yet finished. */
    public function isRunning(): bool
    {
        if ($this->isPending()) {
            return true;
        }

        return $this->isScanning();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function progressPercent(): int
    {
        if (! $this->checks_total) {
            return 0;
        }

        return (int) round(($this->checks_done / $this->checks_total) * 100);
    }

    public function grade(): ?Grade
    {
        if ($this->grade_letter === null || $this->grade_score === null) {
            return null;
        }

        return new Grade($this->grade_letter, $this->grade_score);
    }

    public function recordResult(ScanResult $result): void
    {
        $this->update([
            'status' => 'completed',
            'grade_letter' => $result->grade->letter,
            'grade_score' => $result->grade->score,
            'findings' => array_map(fn (Finding $f): array => [
                'checkId' => $f->checkId,
                'severity' => $f->severity->value,
                'title' => $f->title,
                'explanation' => $f->explanation,
                'fix' => $f->fix,
                'evidence' => $f->evidence,
            ], $result->findings),
            'passed' => $result->passed,
            'completed_at' => now(),
        ]);
    }
}
