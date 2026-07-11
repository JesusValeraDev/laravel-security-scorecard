<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Http\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\MonitorModel;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\ScanModel;
use Modules\Scorecard\Infrastructure\Verification\DomainVerifier;

#[Layout('components.layout')]
class ScanReport extends Component
{
    public string $token;

    #[Validate('required|email|max:255')]
    public string $email = '';

    /** Set once a monitor has been created for this report, so we can track its state. */
    public ?string $monitorToken = null;

    public function mount(ScanModel $scan): void
    {
        $this->token = $scan->token;
    }

    public function monitor(): ?MonitorModel
    {
        return $this->monitorToken
            ? MonitorModel::where('token', $this->monitorToken)->first()
            : null;
    }

    /**
     * Register interest in monitoring this site. The monitor stays unverified (and
     * unscanned) until the owner proves domain control. The current grade is stored
     * as the baseline so future drops can be detected once verified.
     */
    public function watch(): void
    {
        $this->validate();

        $scan = $this->scan();

        if (! $scan->isCompleted()) {
            return;
        }

        $monitor = MonitorModel::updateOrCreate(
            ['host' => $scan->host, 'email' => $this->email],
            [
                'url' => $scan->url,
                'active' => true,
                'last_grade_letter' => $scan->grade_letter,
                'last_grade_score' => $scan->grade_score,
                'last_scan_id' => $scan->id,
            ],
        );

        $this->monitorToken = $monitor->token;
    }

    /**
     * Check whether the owner has published the DNS/meta/well-known proof yet.
     */
    public function verify(DomainVerifier $verifier): void
    {
        $monitor = $this->monitor();

        if (! $monitor instanceof MonitorModel || $monitor->isVerified()) {
            return;
        }

        $verified = $verifier->verify($monitor->host, $monitor->url, (string) $monitor->verification_token);

        if (! $verified) {
            $this->addError('verify', "We couldn't find the verification record yet. It can take a few minutes to propagate — try again shortly.");

            return;
        }

        $monitor->markVerified();
        $monitor->update(['last_scanned_at' => now()]);
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
        $monitor = $this->monitor();

        return view('livewire.scan-report', [
            'scan' => $this->scan(),
            'monitor' => $monitor,
            'verificationName' => DomainVerifier::NAME,
            'verificationValue' => $monitor instanceof MonitorModel
                ? DomainVerifier::NAME.'='.$monitor->verification_token
                : null,
        ]);
    }
}
