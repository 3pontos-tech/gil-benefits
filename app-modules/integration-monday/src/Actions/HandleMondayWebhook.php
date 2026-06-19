<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Actions;

use TresPontosTech\IntegrationMonday\DTO\MondayWebhookDTO;
use TresPontosTech\IntegrationMonday\Events\MondayItemColumnChanged;

/**
 * Translates a parsed Monday webhook into a domain event. Holds no support
 * knowledge — it only re-emits what Monday reported; consumers decide relevance.
 */
final class HandleMondayWebhook
{
    public function handle(MondayWebhookDTO $dto): void
    {
        event(new MondayItemColumnChanged(
            boardId: $dto->boardId,
            itemId: $dto->itemId,
            columnId: $dto->columnId,
            index: $dto->index,
        ));
    }
}
