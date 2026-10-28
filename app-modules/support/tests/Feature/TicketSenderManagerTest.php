<?php

declare(strict_types=1);

use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Senders\EmailTicketSender;
use TresPontosTech\Support\TicketSenderManager;

it('ships the e-mail sender as the default driver', function (): void {
    $manager = resolve(TicketSenderManager::class);

    expect($manager->getSender(TicketDestinationTypeEnum::Email))->toBeInstanceOf(EmailTicketSender::class)
        ->and($manager->driver())->toBeInstanceOf(EmailTicketSender::class);
});
