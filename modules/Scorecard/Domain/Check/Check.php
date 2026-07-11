<?php

declare(strict_types=1);

namespace Modules\Scorecard\Domain\Check;

use Modules\Scorecard\Domain\ValueObject\Finding;
use Modules\Scorecard\Domain\ValueObject\Target;

/**
 * A single passive security check. Implementations issue at most a few GET requests
 * a crawler could make anyway, and return a Finding only when they are certain
 * something is wrong (null otherwise). Never send payloads or attempt exploitation.
 */
interface Check
{
    /** Stable machine identifier, e.g. "env-file-exposed". */
    public function id(): string;

    /** Human-readable name shown in the "what we checked" list. */
    public function title(): string;

    /**
     * The request paths this check issues, so the UI can publish the complete list of what
     * the scanner asks for without anyone re-typing it by hand.
     *
     * @return list<string>
     */
    public function probes(): array;

    public function run(Target $target): ?Finding;
}
