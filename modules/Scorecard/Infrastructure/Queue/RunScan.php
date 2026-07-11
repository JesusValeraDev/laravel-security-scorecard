<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Queue;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Scorecard\Application\ScanRunner;
use Modules\Scorecard\Domain\ValueObject\Target;
use Modules\Scorecard\Infrastructure\Http\Client\ProbeClient;
use Modules\Scorecard\Infrastructure\Http\Client\PublicHostGuard;
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

            if (! app(PublicHostGuard::class)->isPublic($target->origin())) {
                $scan->update(['status' => 'failed', 'error' => 'That host is not publicly reachable.']);

                return;
            }

            // A grade is a claim about a server's responses, so we must have some. Without
            // this, an unreachable site fails every check silently and walks away with an A.
            if (! $this->responds($target)) {
                $scan->update([
                    'status' => 'failed',
                    'error' => 'We got no response from '.$target->host.'. Check the URL and try again.',
                ]);

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
        } catch (Throwable $e) {
            $scan->update([
                'status' => 'failed',
                'error' => 'The scan could not be completed. The site may be unreachable.',
            ]);

            report($e);
        }
    }

    /**
     * Any answer counts — a 404 or a 500 is still a server we can grade. Only a connection
     * failure (no DNS, no route, no TLS, timeout) means there is nothing there to scan.
     */
    private function responds(Target $target): bool
    {
        try {
            app(ProbeClient::class)->get($target->origin());

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
