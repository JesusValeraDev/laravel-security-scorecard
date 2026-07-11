<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Console;

use Illuminate\Console\Command;
use Modules\Scorecard\Application\ScanRunner;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\MonitorModel;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\ScanModel;
use Modules\Scorecard\Infrastructure\Queue\RunScan;

class RescanMonitors extends Command
{
    #[\Override]
    protected $signature = 'monitors:rescan';

    #[\Override]
    protected $description = 'Queue a re-scan for every monitor that is due, alerting on grade drops';

    public function handle(): int
    {
        $due = MonitorModel::where('active', true)
            ->whereNotNull('verified_at')
            ->get()
            ->filter
            ->isDue();

        if ($due->isEmpty()) {
            $this->info('No monitors are due for a re-scan.');

            return self::SUCCESS;
        }

        $total = app(ScanRunner::class)->checkCount();

        foreach ($due as $monitor) {
            $scan = ScanModel::create([
                'monitor_id' => $monitor->id,
                'url' => $monitor->url,
                'host' => $monitor->host,
                'status' => 'pending',
                'checks_total' => $total,
            ]);

            RunScan::dispatch($scan->id);
        }

        $this->info("Queued {$due->count()} monitor re-scan(s).");

        return self::SUCCESS;
    }
}
