<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Http\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\ScanModel;

#[Layout('components.layout')]
class ScanReport extends Component
{
    public string $token;

    public function mount(ScanModel $scan): void
    {
        $this->token = $scan->token;
    }

    /**
     * Re-queried every render so polling always reflects the latest job progress.
     */
    public function scan(): ScanModel
    {
        return ScanModel::where('token', $this->token)->firstOrFail();
    }

    public function render(): Factory|View
    {
        return view('livewire.scan-report', [
            'scan' => $this->scan(),
        ]);
    }
}
