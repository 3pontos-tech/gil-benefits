<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use TresPontosTech\IntegrationMonday\Actions\HandleMondayWebhook;
use TresPontosTech\IntegrationMonday\DTO\MondayWebhookDTO;
use TresPontosTech\IntegrationMonday\Events\MondayItemColumnChanged;

it('re-emits a Monday column change as a domain event', function (): void {
    Event::fake([MondayItemColumnChanged::class]);

    $dto = MondayWebhookDTO::fromArray([
        'event' => [
            'type' => 'update_column_value',
            'boardId' => 111,
            'pulseId' => 987654,
            'columnId' => 'status',
            'value' => ['label' => ['index' => 3, 'text' => 'Encerrado']],
        ],
    ]);

    resolve(HandleMondayWebhook::class)->handle($dto);

    Event::assertDispatched(
        MondayItemColumnChanged::class,
        fn (MondayItemColumnChanged $event): bool => $event->itemId === '987654'
            && $event->columnId === 'status'
            && $event->index === 3,
    );
});
