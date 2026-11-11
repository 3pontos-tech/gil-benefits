<?php

declare(strict_types=1);

namespace App\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;

/**
 * Base dos avisos descritos por um NotificationContent. A notificação concreta só monta o
 * conteúdo; esta base o converte para cada canal, então nenhum aviso conhece o formato do
 * Filament ou de um provedor de push.
 */
abstract class ContentNotification extends Notification
{
    /**
     * Canal de push, registrado pelo módulo da API. Os módulos pedem o canal pelo nome, sem
     * depender de quem entrega.
     */
    public const string PUSH_CHANNEL = 'expo-push';

    abstract public function content(): NotificationContent;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->withPush($notifiable, ['database']);
    }

    /**
     * Acrescenta o push quando o aviso tem texto para o celular e esta entrega deve ir até lá.
     *
     * @param  list<string>  $channels
     * @return list<string>
     */
    protected function withPush(object $notifiable, array $channels): array
    {
        if ($this->content()->push instanceof NotificationPush && $this->shouldPush($notifiable)) {
            $channels[] = self::PUSH_CHANNEL;
        }

        return $channels;
    }

    /**
     * Um aviso pode recusar o push conforme quem causou o evento: quem fez a ação já sabe dela.
     */
    protected function shouldPush(object $notifiable): bool
    {
        return true;
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

        $notification = FilamentNotification::make()
            ->title($content->title)
            ->body($content->body)
            ->status($content->tone->value);

        if ($content->link instanceof NotificationLink) {
            $notification->actions([
                Action::make('open')
                    ->label($content->link->label)
                    ->url($content->link->url)
                    ->openUrlInNewTab()
                    ->markAsRead(),
            ]);
        }

        return $notification;
    }
}
