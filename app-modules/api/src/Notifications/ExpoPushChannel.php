<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Notifications;

use App\Models\Users\User;
use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationPush;
use Illuminate\Notifications\Notification;
use TresPontosTech\Api\Jobs\SendPushNotificationJob;
use TresPontosTech\Api\Models\PushToken;

/**
 * Canal `expo-push` dos avisos com texto de push. Monta a mensagem e deixa a entrega para a fila,
 * para a ação que causou o aviso não esperar a Expo. Quem não tem aparelho registrado não gera
 * job nenhum.
 */
final readonly class ExpoPushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User || ! $notification instanceof ContentNotification) {
            return;
        }

        $content = $notification->content();

        if (! $content->push instanceof NotificationPush) {
            return;
        }

        if (! PushToken::query()->where('user_id', $notifiable->getKey())->exists()) {
            return;
        }

        dispatch(new SendPushNotificationJob((string) $notifiable->getKey(), $this->message($notifiable, $notification, $content, $content->push)));
    }

    /**
     * O payload que o app lê: o tipo, os ids para abrir a tela certa e, quando o aviso também
     * ficou no sino, o id da linha para marcá-la como lida. O link da sala nunca vai aqui.
     *
     * @return array<string, mixed>
     */
    private function message(User $notifiable, ContentNotification $notification, NotificationContent $content, NotificationPush $push): array
    {
        $isStored = in_array('database', $notification->via($notifiable), true);

        $data = array_filter([
            'type' => $content->kind->value,
            'notification_id' => $isStored ? $notification->id : null,
            'appointment_id' => $content->appointmentId,
            'document_id' => $content->documentId,
            ...$push->data,
        ], fn (?string $value): bool => $value !== null);

        return array_filter([
            'title' => $push->title,
            'body' => $push->body,
            'data' => $data,
            'sound' => 'default',
            'priority' => $push->urgent ? 'high' : 'default',
            'expiration' => $push->expiresAt?->getTimestamp(),
        ], fn (mixed $value): bool => $value !== null);
    }
}
