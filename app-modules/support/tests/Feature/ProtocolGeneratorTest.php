<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Services\ProtocolGenerator;

beforeEach(function (): void {
    Date::setTestNow('2026-06-11 12:00:00');
});

afterEach(function (): void {
    Date::setTestNow();
    // Drop any global scope registered during a test so it does not leak.
    SupportTicket::clearBootedModels();
});

/**
 * Inserts a ticket row directly, bypassing the action (no job/protocol logic).
 */
function makeTicket(string $protocol, ?string $companyId = null): SupportTicket
{
    return SupportTicket::query()->create([
        'protocol' => $protocol,
        'company_id' => $companyId,
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'subject',
        'description' => 'description',
        'status' => SupportTicketStatusEnum::Pending,
        'environment' => 'testing',
    ]);
}

function generate(): string
{
    return resolve(ProtocolGenerator::class)->generate();
}

it('starts at 0001 when there are no tickets for the year', function (): void {
    expect(generate())->toBe('SUP-2026-0001');
});

it('increments from the highest protocol of the year', function (): void {
    makeTicket('SUP-2026-0001');

    expect(generate())->toBe('SUP-2026-0002');
});

it('numbers globally, ignoring an active tenant global scope (regression)', function (): void {
    // A ticket already exists (e.g. opened by a guest, no company).
    makeTicket('SUP-2026-0001');

    // Reproduce the bug: the `company_tenancy` scope hides rows from other tenants.
    // whereRaw('1 = 0') is the strongest case — it hides every row.
    SupportTicket::addGlobalScope('company_tenancy', fn ($query) => $query->whereRaw('1 = 0'));

    // Without withoutGlobalScopes() the generator would read 0 and return SUP-2026-0001,
    // colliding with the existing global protocol.
    expect(generate())->toBe('SUP-2026-0002');
});

it('resets the sequence per year', function (): void {
    makeTicket('SUP-2026-0001');

    Date::setTestNow('2027-01-02 09:00:00');

    expect(generate())->toBe('SUP-2027-0001');
});

it('ignores protocols from other years when incrementing', function (): void {
    makeTicket('SUP-2025-0009');

    expect(generate())->toBe('SUP-2026-0001');
});

it('parses sequences longer than four digits', function (): void {
    // %04d is a minimum width, so past 9999 the suffix grows — the parser must read
    // the whole numeric part, not the last four characters.
    makeTicket('SUP-2026-10000');

    expect(generate())->toBe('SUP-2026-10001');
});
