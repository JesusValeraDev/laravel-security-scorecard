<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Http\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\MonitorModel;

#[Layout('components.layout')]
class ManageMonitor extends Component
{
    public string $token;

    public function mount(MonitorModel $monitor): void
    {
        $this->token = $monitor->token;
    }

    public function monitor(): MonitorModel
    {
        return MonitorModel::where('token', $this->token)->firstOrFail();
    }

    public function unsubscribe(): void
    {
        $this->monitor()->unsubscribe();
    }

    public function resubscribe(): void
    {
        $this->monitor()->update(['active' => true]);
    }

    public function render(): Factory|View
    {
        return view('livewire.manage-monitor', ['monitor' => $this->monitor()]);
    }
}
