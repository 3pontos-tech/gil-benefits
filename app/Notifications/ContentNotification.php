<?php

declare(strict_types=1);

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;

/**
 * Base dos avisos descritos por um NotificationContent. A notificação concreta só monta o
 * conteúdo; esta base o converte para cada canal, então nenhum aviso conhece o formato do
 * Filament ou de um provedor de push.
 */
abstract class ContentNotification extends Notification
{
    abstract public function content(): NotificationContent;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * A coluna `type` guarda o tipo do aviso, que a API usa para filtrar o que o app exibe.
     */
    public function databaseType(object $notifiable): string
    {
        return $this->content()->kind->value;
    }

    /**
     * Linha no formato do Filament (o sino do painel lê só as chaves que conhece) mais o
     * tipo e os ids que o app usa para abrir a tela certa.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $content = $this->content();

        return [
            ...$this->toFilament()->getDatabaseMessage(),
            ...array_filter([
                'kind' => $content->kind->value,
                'appointment_id' => $content->appointmentId,
                'document_id' => $content->documentId,
            ], fn (?string $value): bool => $value !== null),
        ];
    }

    /**
     * Mostra o mesmo aviso como toast para quem está na tela que causou o evento, como os
     * envios do painel faziam com `->send()`.
     */
    public function flash(): void
    {
        $this->toFilament()->send();
    }

    private function toFilament(): FilamentNotification
    {
        $content = $this->content();

        return FilamentNotification::make()
            ->title($content->title)
            ->body($content->body)
            ->status($content->tone->value);
    }
}
