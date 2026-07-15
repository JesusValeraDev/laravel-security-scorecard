<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Http\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Modules\Scorecard\Application\ScanRunner;
use Modules\Scorecard\Domain\ValueObject\Finding;
use Modules\Scorecard\Domain\ValueObject\Target;
use Modules\Scorecard\Infrastructure\Http\Client\ProbeClient;
use Modules\Scorecard\Infrastructure\Http\Client\PublicHostGuard;
use Throwable;

#[Layout('components.layout')]
class ScanForm extends Component
{
    #[Validate('required|string|max:255')]
    public string $url = '';

    /**
     * The scan runs inline (sync queue) and its result lives only on the component, so a
     * page refresh clears it. Nothing is persisted — there is no database.
     */
    public bool $scanned = false;

    public ?string $failure = null;

    public ?string $host = null;

    public ?string $gradeLetter = null;

    public ?int $gradeScore = null;

    /** @var list<array{severity: string, title: string, explanation: string, fix: string, evidence: ?string}> */
    public array $findings = [];

    /** @var list<string> */
    public array $passed = [];

    public function scan(): void
    {
        $this->validate();
        $this->reset('scanned', 'failure', 'host', 'gradeLetter', 'gradeScore', 'findings', 'passed');

        try {
            $target = Target::fromUrl($this->url);
        } catch (InvalidArgumentException $e) {
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

        RateLimiter::hit($key);

        // A grade is a claim about a server's responses, so we must have some. Without this,
        // an unreachable site fails every check silently and walks away with an A.
        if (! $this->responds($target)) {
            $this->failure = 'We got no response from '.$target->host.'. Check the URL and try again.';

            return;
        }

        try {
            $result = app(ScanRunner::class)->run($target);
        } catch (Throwable $e) {
            report($e);
            $this->failure = 'The scan could not be completed. The site may be unreachable.';

            return;
        }

        $this->host = $target->host;
        $this->gradeLetter = $result->grade->letter;
        $this->gradeScore = $result->grade->score;
        $this->findings = array_map(static fn (Finding $f): array => [
            'severity' => $f->severity->value,
            'title' => $f->title,
            'explanation' => $f->explanation,
            'fix' => $f->fix,
            'evidence' => $f->evidence,
        ], $result->findings);
        $this->passed = $result->passed;
        $this->scanned = true;
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

        return array_values(
            array_map(
                static fn (array $group): CheckCard => new CheckCard($group['requests'], $group['reveals']),
                $grouped,
            )
        );
    }

    public function render(): Factory|View
    {
        return view('livewire.scan-form', [
            'checks' => $this->checkCards(),
        ]);
    }
}
