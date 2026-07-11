<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Verification;

/**
 * Thin wrapper around DNS TXT resolution so ownership verification can be faked in
 * tests without hitting the network.
 *
 * @return list<string>
 */
class TxtRecordLookup
{
    /**
     * @return list<string>
     */
    public function forHost(string $host): array
    {
        $records = @dns_get_record($host, DNS_TXT) ?: [];

        return array_values(array_filter(array_map(
            static fn (array $record): string => (string) ($record['txt'] ?? ''),
            $records,
        )));
    }
}
