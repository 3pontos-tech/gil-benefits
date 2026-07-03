<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Support;

use TresPontosTech\Support\Enums\SupportTicketStatusEnum;

/**
 * Translates between the SupportTicketStatusEnum and the Monday status column
 * label INDEX. Indexes are config-driven and stable on the board, so renaming,
 * recasing or localizing the labels never breaks the sync.
 */
final class MondayStatusMap
{
    public static function index(SupportTicketStatusEnum $status): int
    {
        return (int) config('monday.status_indexes.' . $status->value);
    }

    public static function fromIndex(int $index): ?SupportTicketStatusEnum
    {
        $value = array_search($index, (array) config('monday.status_indexes', []), strict: true);

        return $value === false ? null : SupportTicketStatusEnum::tryFrom($value);
    }
}
