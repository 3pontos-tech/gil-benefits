<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * O que um aviso diz, independente de como é entregue. Cada canal apresenta este conteúdo
 * no seu formato: o banco (sino do painel e do app) e, quando há `push`, o celular.
 */
final readonly class NotificationContent
{
    public function __construct(
        public NotificationKind $kind,
        public string $title,
        public string $body,
        public NotificationTone $tone,
        public ?string $appointmentId = null,
        public ?string $documentId = null,
        public ?NotificationLink $link = null,
        public ?NotificationPush $push = null,
    ) {}
}
