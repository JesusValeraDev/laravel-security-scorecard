<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Queue;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Scorecard\Application\ScanRunner;
use Modules\Scorecard\Domain\ValueObject\Target;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\ScanModel;
use Throwable;

class RunScan implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 90;

    public function __construct(public string $scanId) {}

    public function handle(): void
    {
        $scan = ScanModel::find($this->scanId);

        if ($scan === null || ! $scan->isPending()) {
            return;
        }

        try {
            $target = Target::fromUrl($scan->url);

            if (! $target->isPubliclyRoutable()) {
                $scan->update(['status' => 'failed', 'error' => 'That host is not publicly reachable.']);

                return;
            }

            $runner = app(ScanRunner::class);

            $scan->update([
                'status' => 'scanning',
                'checks_total' => $runner->checkCount(),
                'checks_done' => 0,
            ]);

            $result = $runner->run($target, function (int $done, int $total, ?string $currentTitle) use ($scan): void {
                $scan->update([
                    'checks_done' => $done,
                    'checks_total' => $total,
                    'current_check' => $currentTitle,
                ]);
            });

            $scan->recordResult($result);

            // If this scan came from a monitor, alert the owner on a grade drop.
            $scan->monitor?->evaluateAfterScan($scan->fresh());
        } catch (Throwable $e) {
            $scan->update([
                'status' => 'failed',
                'error' => 'The scan could not be completed. The site may be unreachable.',
            ]);

            report($e);
        }
    }
}
