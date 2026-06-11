<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Services;

use TresPontosTech\Support\Models\SupportTicket;

class ProtocolGenerator
{
    /**
     * Generates the next protocol for the current year (e.g. SUP-2026-0001).
     *
     * withoutGlobalScopes(): SupportTicket carries the `company_tenancy` global scope
     * (Filament tenancy), which injects `where company_id = <tenant>` into every Eloquent
     * query. Since the `protocol` UNIQUE constraint is GLOBAL (platform-wide) and guest
     * tickets have a null company_id, counting "the highest of the year" within the tenant
     * would only see the current company's tickets → it would restart at 0001 per company
     * → colliding with the global protocol. So the numbering reads the whole table,
     * ignoring the tenant scope.
     *
     * Concurrency: the "read max + insert" window is handled by a retry on the unique
     * constraint in CreateSupportTicketAction — on a race, the loser re-reads (now seeing
     * the winning row) and takes the next number. The UNIQUE constraint is the final guard.
     */
    public function generate(): string
    {
        $year = now()->year;
        $prefix = sprintf('SUP-%d-', $year);

        $lastSequence = SupportTicket::query()
            ->withoutGlobalScopes()
            ->where('protocol', 'like', $prefix . '%')
            ->pluck('protocol')
            ->map(static fn (string $protocol): int => (int) substr($protocol, -4))
            ->max() ?? 0;

        return sprintf('%s%04d', $prefix, $lastSequence + 1);
    }
}
