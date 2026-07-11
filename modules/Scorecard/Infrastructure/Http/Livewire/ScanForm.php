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
use Modules\Scorecard\Infrastructure\Http\Client\PublicHostGuard;
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

        // Resolves the host too: a public-looking name can still point at a private address.
        if (! app(PublicHostGuard::class)->isPublic($target->origin())) {
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

    /**
     * Plain-English consequence per check. The requests themselves come from the checks, so
     * this map only supplies wording — a check with no entry here falls back to its title,
     * and a test asserts every registered check is covered.
     */
    private const array REVEALS = [
        'env-file-exposed' => 'Your app key, database password and third-party secrets.',
        'git-directory-exposed' => 'Your full source history, clonable by anyone.',
        'composer-lock-exposed' => 'The exact version of every dependency you run.',
        'log-file-exposed' => 'Stack traces, SQL queries and request data.',
        'ignition-exposed' => 'The debug endpoint behind CVE-2021-3129.',
        'telescope-exposed' => 'Every request, query, job and mail you handle.',
        'horizon-exposed' => 'Your queue workload, and control of its workers.',
        'pulse-exposed' => 'Slow queries, exceptions and app internals.',
        'directory-listing' => 'Browsable file listings of your web root.',
        'https-redirect' => 'Whether plain HTTP is redirected up to HTTPS.',
        'cookie-security' => 'Whether the session cookie is Secure, HttpOnly and SameSite.',
        'server-version-disclosure' => 'Banners naming the exact version of your server software.',
        'security-headers' => 'Security headers: HSTS, CSP, frame and content-type options.',
    ];

    /**
     * The published "what we request" list, generated from the registered checks so it cannot
     * drift from the requests the scanner actually sends. Checks that share a request share a
     * card, because that is one request answered once.
     *
     * @return list<CheckCard>
     */
    public function checkCards(): array
    {
        /** @var array<string, array{requests: list<string>, reveals: list<string>}> $grouped */
        $grouped = [];

        foreach (app(ScanRunner::class)->manifest() as $entry) {
            $key = implode(' ', $entry->probes);

            $grouped[$key]['requests'] = $entry->probes;
            $grouped[$key]['reveals'][] = self::REVEALS[$entry->id] ?? $entry->title;
        }

        return array_values(array_map(
            static fn (array $group): CheckCard => new CheckCard($group['requests'], $group['reveals']),
            $grouped,
        ));
    }

    public function render(): Factory|View
    {
        return view('livewire.scan-form', [
            'checks' => $this->checkCards(),
        ]);
    }
}
