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
 * Detects a publicly readable composer.lock, which discloses the exact version of
 * every backend dependency — a precise CVE shopping list for an attacker. Confirmed
 * by the file's distinctive JSON signature, not a bare 200.
 */
final readonly class ComposerLockExposedCheck implements Check
{
    public function __construct(private ProbeClient $client) {}

    public function id(): string
    {
        return 'composer-lock-exposed';
    }

    public function title(): string
    {
        return 'composer.lock is not public';
    }

    /** @return list<string> */
    public function probes(): array
    {
        return ['/composer.lock'];
    }

    public function run(Target $target): ?Finding
    {
        try {
            $response = $this->client->get($target->url('composer.lock'));
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $body = $response->body();

        // A real composer.lock is JSON with these top-level keys.
        if (! str_contains($body, '"_readme"') || ! str_contains($body, '"content-hash"')) {
            return null;
        }

        return new Finding(
            checkId: $this->id(),
            severity: Severity::Medium,
            title: 'Your composer.lock is publicly readable',
            explanation: 'The file at '.$target->url('composer.lock').' lists the exact '
                .'version of every PHP dependency in your app. Attackers use it to find '
                .'dependencies with known vulnerabilities to target.',
            fix: 'Ensure your web root is the public/ directory so project files like '
                .'composer.lock and composer.json are never served. Block them at the web '
                .'server as defense in depth.',
            evidence: 'Valid composer.lock JSON returned.',
        );
    }
}
