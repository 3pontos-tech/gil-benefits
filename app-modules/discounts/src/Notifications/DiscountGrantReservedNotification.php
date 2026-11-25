<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Support\DiscountInviteUrl;

final class DiscountGrantReservedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DiscountGrant $grant)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $percent = $this->grant->discount->percent_off;
        $firstName = Str::of((string) $this->grant->name)->trim()->before(' ')->toString();

        return (new MailMessage)
            ->subject(sprintf('Seu desconto de %d%% na Flamma está reservado', $percent))
            ->greeting($firstName !== '' ? "Olá, {$firstName}!" : 'Olá!')
            ->line(sprintf('Você concluiu o diagnóstico financeiro com a Clau e reservamos para você %d%% de desconto vitalício no plano pessoal da Flamma.', $percent))
            ->line('Para usar, crie sua conta pelo botão abaixo com este e-mail e o mesmo CPF que você informou à Clau, e assine o plano pessoal até ' . $this->grant->expires_at->format('d/m/Y') . '.')
            ->action('Ativar minha conta', DiscountInviteUrl::for($this->grant))
            ->line('Se você não pediu isso, pode ignorar este e-mail: a reserva expira sozinha.');
    }
}
