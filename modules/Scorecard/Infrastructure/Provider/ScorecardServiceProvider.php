<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Scorecard\Application\ScanRunner;
use Modules\Scorecard\Infrastructure\Check\ComposerLockExposedCheck;
use Modules\Scorecard\Infrastructure\Check\CookieSecurityCheck;
use Modules\Scorecard\Infrastructure\Check\DirectoryListingCheck;
use Modules\Scorecard\Infrastructure\Check\EnvFileExposedCheck;
use Modules\Scorecard\Infrastructure\Check\GitDirectoryExposedCheck;
use Modules\Scorecard\Infrastructure\Check\HorizonExposedCheck;
use Modules\Scorecard\Infrastructure\Check\HttpsRedirectCheck;
use Modules\Scorecard\Infrastructure\Check\IgnitionExposedCheck;
use Modules\Scorecard\Infrastructure\Check\LogFileExposedCheck;
use Modules\Scorecard\Infrastructure\Check\PulseExposedCheck;
use Modules\Scorecard\Infrastructure\Check\SecurityHeadersCheck;
use Modules\Scorecard\Infrastructure\Check\ServerVersionDisclosureCheck;
use Modules\Scorecard\Infrastructure\Check\TelescopeExposedCheck;
use Modules\Scorecard\Infrastructure\Console\RescanMonitors;
use Modules\Scorecard\Infrastructure\Http\Livewire\ManageMonitor;
use Modules\Scorecard\Infrastructure\Http\Livewire\ScanForm;
use Modules\Scorecard\Infrastructure\Http\Livewire\ScanReport;

final class ScorecardServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        // Assemble the concrete checks here so the Application ScanRunner stays free
        // of any Infrastructure dependency. Order defines display/run order.
        $this->app->singleton(ScanRunner::class, static fn (): ScanRunner => new ScanRunner([
            new EnvFileExposedCheck,
            new GitDirectoryExposedCheck,
            new ComposerLockExposedCheck,
            new LogFileExposedCheck,
            new IgnitionExposedCheck,
            new TelescopeExposedCheck,
            new HorizonExposedCheck,
            new PulseExposedCheck,
            new DirectoryListingCheck,
            new HttpsRedirectCheck,
            new CookieSecurityCheck,
            new ServerVersionDisclosureCheck,
            new SecurityHeadersCheck,
        ]));
    }

    public function boot(): void
    {
        Livewire::component('scan-form', ScanForm::class);
        Livewire::component('scan-report', ScanReport::class);
        Livewire::component('manage-monitor', ManageMonitor::class);

        if ($this->app->runningInConsole()) {
            $this->commands([RescanMonitors::class]);
        }
    }
}
