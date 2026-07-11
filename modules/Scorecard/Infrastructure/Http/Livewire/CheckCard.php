<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Http\Livewire;

/**
 * The landing page groups by request, not by check: one card per thing we ask your server
 * for, listing everything that single response would give away. Four checks read the same
 * homepage response, so they share one card rather than repeating "/" four times.
 */
final readonly class CheckCard
{
    /**
     * @param  list<string>  $requests
     * @param  list<string>  $reveals
     */
    public function __construct(
        public array $requests,
        public array $reveals,
    ) {}

    /** The homepage card carries several checks, so it earns the full width of the grid. */
    public function isWide(): bool
    {
        return count($this->reveals) > 1;
    }
}
