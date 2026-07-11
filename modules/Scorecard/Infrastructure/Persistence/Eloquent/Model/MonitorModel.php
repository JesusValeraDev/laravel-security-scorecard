<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Scorecard\Domain\ValueObject\Grade;
use Modules\Scorecard\Infrastructure\Mail\GradeDroppedMail;

/**
 * A watched site: we re-scan it on a schedule and email the recipient when its
 * security grade drops to a worse letter than the last time we checked.
 *
 * @property string $token
 * @property string $url
 * @property string $host
 * @property string $email
 * @property int $frequency_hours
 * @property bool $active
 * @property ?string $last_grade_letter
 * @property ?int $last_grade_score
 * @property ?int $last_scan_id
 * @property ?string $verification_token
 * @property ?Carbon $verified_at
 */
class MonitorModel extends Model
{
    /** @use HasUlids<MonitorModel> */
    use HasUlids;

    #[\Override]
    protected $table = 'monitors';

    #[\Override]
    protected $guarded = [];

    /** In-memory defaults so freshly-made instances behave before a DB round-trip. */
    #[\Override]
    protected $attributes = [
        'active' => true,
        'frequency_hours' => 24,
    ];

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (MonitorModel $monitor): void {
            $monitor->verification_token ??= Str::lower(Str::random(32));
        });
    }

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
            'active' => 'boolean',
            'frequency_hours' => 'integer',
            'last_grade_score' => 'integer',
            'last_scanned_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function markVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }

    public function unsubscribe(): void
    {
        $this->update(['active' => false]);
    }

    /**
     * Whether this monitor is ready for another re-scan. Evaluated in PHP so the
     * per-monitor interval works identically on SQLite and PostgreSQL. Only verified,
     * active monitors are ever scanned.
     */
    public function isDue(): bool
    {
        if (! $this->active || ! $this->isVerified()) {
            return false;
        }

        if ($this->last_scanned_at === null) {
            return true;
        }

        return $this->last_scanned_at->addHours($this->frequency_hours)->isPast();
    }

    /**
     * Called after a monitor-originated scan finishes. Emails the recipient if the
     * grade dropped to a worse letter, then records the new baseline.
     */
    public function evaluateAfterScan(ScanModel $scan): void
    {
        if (! $scan->isCompleted() || $scan->grade_letter === null) {
            return;
        }

        $previousLetter = $this->last_grade_letter;

        $dropped = $previousLetter !== null
            && Grade::rank($scan->grade_letter) < Grade::rank($previousLetter);

        // Alerts are gated so the app can run for free with no mail provider.
        if ($dropped && (bool) config('scorecard.alerts_enabled')) {
            Mail::to($this->email)->send(new GradeDroppedMail($this, $scan, $previousLetter));
        }

        $this->update([
            'last_grade_letter' => $scan->grade_letter,
            'last_grade_score' => $scan->grade_score,
            'last_scan_id' => $scan->id,
            'last_scanned_at' => now(),
        ]);
    }
}
