<?php

declare(strict_types=1);

use TresPontosTech\IntegrationMonday\Support\MondayStatusMap;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;

beforeEach(function (): void {
    config(['monday.status_indexes' => [
        'pending' => 17,
        'in_progress' => 0,
        'resolved' => 2,
        'closed' => 3,
    ]]);
});

it('maps an enum to its board label index', function (): void {
    expect(MondayStatusMap::index(SupportTicketStatusEnum::InProgress))->toBe(0)
        ->and(MondayStatusMap::index(SupportTicketStatusEnum::Closed))->toBe(3);
});

it('maps a board label index back to its enum', function (): void {
    expect(MondayStatusMap::fromIndex(0))->toBe(SupportTicketStatusEnum::InProgress)
        ->and(MondayStatusMap::fromIndex(3))->toBe(SupportTicketStatusEnum::Closed);
});

it('returns null for an unknown index', function (): void {
    expect(MondayStatusMap::fromIndex(99))->toBeNull();
});
