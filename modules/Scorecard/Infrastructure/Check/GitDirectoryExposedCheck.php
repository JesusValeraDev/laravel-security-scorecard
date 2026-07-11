<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Check;

use Illuminate\Support\Facades\Http;
use Modules\Scorecard\Domain\Check\Check;
use Modules\Scorecard\Domain\ValueObject\Finding;
use Modules\Scorecard\Domain\ValueObject\Severity;
use Modules\Scorecard\Domain\ValueObject\Target;
use Throwable;

/**
 * Detects an exposed .git directory, which lets an attacker reconstruct your entire
 * source history. Confirmed by the signature of a real Git config/HEAD file.
 */
final class GitDirectoryExposedCheck implements Check
{
    public function id(): string
    {
        return 'git-directory-exposed';
    }

    public function title(): string
    {
        return 'Git directory (.git) is not public';
    }

    public function run(Target $target): ?Finding
    {
        try {
            $head = Http::timeout(8)->get($target->url('.git/HEAD'));
        } catch (Throwable) {
            return null;
        }

        if (! $head->successful()) {
            return null;
        }

        // A real .git/HEAD is a tiny file that starts with "ref: refs/".
        if (! str_starts_with(trim($head->body()), 'ref: refs/')) {
            return null;
        }

        return new Finding(
            checkId: $this->id(),
            severity: Severity::Critical,
            title: 'Your .git directory is publicly accessible',
            explanation: 'Your version control metadata is reachable at '
                .$target->url('.git/').'. Tools can clone your full source code and history '
                .'from it — including any secrets ever committed.',
            fix: 'Block access to dotfiles at the web server, and make sure the web root is '
                .'the public/ directory. Never deploy the .git folder to production.',
            evidence: 'Valid .git/HEAD found.',
        );
    }
}
