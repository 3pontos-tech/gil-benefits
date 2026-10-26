<?php

declare(strict_types=1);

use TresPontosTech\IntegrationMonday\Senders\MondayTicketSenderAdapter;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\TicketSenderManager;

it('plugs the Monday sender into the support manager', function (): void {
    expect(resolve(TicketSenderManager::class)->getSender(TicketDestinationTypeEnum::Monday))
        ->toBeInstanceOf(MondayTicketSenderAdapter::class);
});
