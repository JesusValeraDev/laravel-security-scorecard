<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Http\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Modules\Scorecard\Application\ScanRunner;
use Modules\Scorecard\Domain\ValueObject\Target;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\ScanModel;
use Modules\Scorecard\Infrastructure\Queue\RunScan;

#[Layout('components.layout')]
class ScanForm extends Component
{
    #[Validate('required|string|max:255')]
    public string $url = '';

    public function scan()
    {
        $this->validate();

        try {
            $target = Target::fromUrl($this->url);
        } catch (\InvalidArgumentException $e) {
            $this->addError('url', $e->getMessage());

            return;
        }

        if (! $target->isPubliclyRoutable()) {
            $this->addError('url', 'We can only scan public sites, not local or private hosts.');

            return;
        }

        $key = 'scan:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 8)) {
            $this->addError('url', 'You are scanning a bit fast. Try again in a minute.');

            return;
        }

        RateLimiter::hit($key, decaySeconds: 60);

        $scan = ScanModel::create([
            'url' => $target->origin(),
            'host' => $target->host,
            'status' => 'pending',
            // Seed the total so the progress bar has a denominator before the job starts.
            'checks_total' => app(ScanRunner::class)->checkCount(),
        ]);

        // Queue the scan; the report page streams live progress until it completes.
        RunScan::dispatch($scan->id);

        return $this->redirect(route('report', $scan), navigate: true);
    }

    public function render(): Factory|View
    {
        return view('livewire.scan-form');
    }
}
