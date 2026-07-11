<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Check;

use Modules\Scorecard\Domain\Check\Check;
use Modules\Scorecard\Domain\ValueObject\Finding;
use Modules\Scorecard\Domain\ValueObject\Severity;
use Modules\Scorecard\Domain\ValueObject\Target;
use Modules\Scorecard\Infrastructure\Http\Client\ProbeClient;
use Throwable;

/**
 * Detects a reachable Ignition execute-solution endpoint — the surface of the
 * CVE-2021-3129 remote code execution bug. Detection is by presence only: we issue
 * a bare GET and never send a solution payload.
 */
final readonly class IgnitionExposedCheck implements Check
{
    public function __construct(private ProbeClient $client) {}

    public function id(): string
    {
        return 'ignition-exposed';
    }

    public function title(): string
    {
        return 'Ignition RCE endpoint is not reachable';
    }

    /** @return list<string> */
    public function probes(): array
    {
        return ['/_ignition/execute-solution'];
    }

    public function run(Target $target): ?Finding
    {
        try {
            // A bare GET. The route only accepts POST, so a reachable endpoint answers
            // 405 Method Not Allowed; a patched/absent one answers 404. No payload sent.
            $response = $this->client->get($target->url('_ignition/execute-solution'));
        } catch (Throwable) {
            return null;
        }

        if ($response->status() !== 405) {
            return null;
        }

        return new Finding(
            checkId: $this->id(),
            severity: Severity::Critical,
            title: 'The Ignition execute-solution endpoint is exposed',
            explanation: 'The endpoint behind CVE-2021-3129 is reachable at '
                .$target->url('_ignition/execute-solution').'. On vulnerable versions this '
                .'allows unauthenticated remote code execution. Its presence means debug '
                .'mode is on in production.',
            fix: 'Set APP_DEBUG=false in production, and update facade/ignition to a patched '
                .'release. Ignition should never be reachable on a live site.',
            evidence: 'Endpoint responded 405 to GET (route present, POST-only).',
        );
    }
}
