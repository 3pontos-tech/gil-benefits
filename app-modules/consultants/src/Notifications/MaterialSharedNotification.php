<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationPush;
use App\Notifications\NotificationTone;
use TresPontosTech\Consultants\Models\Document;

/**
 * O consultor compartilhou um material com a pessoa, muitas vezes algo para ela preencher antes
 * do próximo encontro. Fica no sino sempre; vai ao celular só no primeiro de uma leva, para quem
 * recebe cinco materiais seguidos ganhar um push e não cinco.
 */
final class MaterialSharedNotification extends ContentNotification
{
    public function __construct(
        private readonly Document $document,
        private readonly bool $firstOfBurst = true,
    ) {}

    public function content(): NotificationContent
    {
        $title = __('consultants::resources.notifications.material_shared.title');
        $body = __('consultants::resources.notifications.material_shared.body');

        return new NotificationContent(
            kind: NotificationKind::MaterialShared,
            title: $title,
            body: $body,
            tone: NotificationTone::Info,
            documentId: (string) $this->document->getKey(),
            push: new NotificationPush(title: $title, body: $body),
        );
    }

    protected function shouldPush(object $notifiable): bool
    {
        return $this->firstOfBurst;
    }
}
