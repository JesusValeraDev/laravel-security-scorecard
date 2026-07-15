<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Provider;

use Illuminate\Contracts\Foundation\Application;
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
use Modules\Scorecard\Infrastructure\Http\Client\ProbeClient;
use Modules\Scorecard\Infrastructure\Http\Client\PublicHostGuard;
use Modules\Scorecard\Infrastructure\Http\Livewire\ScanForm;

final class ScorecardServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        // One guard and one client for the whole scan, so DNS resolution is cached.
        $this->app->singleton(PublicHostGuard::class);
        $this->app->singleton(ProbeClient::class);

        // Assemble the concrete checks here so the Application ScanRunner stays free
        // of any Infrastructure dependency. Order defines display/run order.
        $this->app->singleton(ScanRunner::class, static function (Application $app): ScanRunner {
            $client = $app->make(ProbeClient::class);

            return new ScanRunner([
                new EnvFileExposedCheck($client),
                new GitDirectoryExposedCheck($client),
                new ComposerLockExposedCheck($client),
                new LogFileExposedCheck($client),
                new IgnitionExposedCheck($client),
                new TelescopeExposedCheck($client),
                new HorizonExposedCheck($client),
                new PulseExposedCheck($client),
                new DirectoryListingCheck($client),
                new HttpsRedirectCheck($client),
                new CookieSecurityCheck($client),
                new ServerVersionDisclosureCheck($client),
                new SecurityHeadersCheck($client),
            ]);
        });
    }

    public function boot(): void
    {
        Livewire::component('scan-form', ScanForm::class);
    }
}
