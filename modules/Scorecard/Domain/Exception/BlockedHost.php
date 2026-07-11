<?php

declare(strict_types=1);

namespace Modules\Scorecard\Domain\Exception;

use RuntimeException;

/**
 * Raised when a URL we were about to request resolves somewhere we must never reach —
 * loopback, link-local (cloud metadata) or private address space. Guards both the URL
 * the user submits and every redirect hop a target tries to send us through.
 */
final class BlockedHost extends RuntimeException
{
    public static function forUrl(string $url): self
    {
        return new self("Refusing to request \"{$url}\": it is not a publicly routable host.");
    }
}
