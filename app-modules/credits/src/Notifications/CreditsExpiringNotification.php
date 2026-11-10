<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Notifications;

use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationPush;
use App\Notifications\NotificationTone;
use Carbon\CarbonInterface;

/**
 * Um crédito da pessoa vence em breve: o de voucher de campanha ou a consulta devolvida como
 * crédito avulso depois da virada do ciclo. Depois da data ele se perde, e para quem só tem o
 * voucher o acesso ao app acaba junto. Agendar resolve, porque o agendamento usa primeiro o
 * crédito que vence antes.
 */
final class CreditsExpiringNotification extends ContentNotification
{
    /**
     * @param  CarbonInterface  $expiresAt  quando o primeiro desses créditos deixa de valer
     */
    public function __construct(private readonly CarbonInterface $expiresAt) {}

    public function content(): NotificationContent
    {
        $title = __('credits::notifications.credits_expiring.title');
        $body = __('credits::notifications.credits_expiring.body', ['date' => $this->lastDay()]);

        return new NotificationContent(
            kind: NotificationKind::CreditsExpiring,
            title: $title,
            body: $body,
            tone: NotificationTone::Warning,
            push: new NotificationPush(title: $title, body: $body, expiresAt: $this->expiresAt),
        );
    }

    /**
     * O último dia em que o crédito ainda vale: um vencimento à meia-noite já não serve naquele dia.
     */
    private function lastDay(): string
    {
        return $this->expiresAt->copy()->timezone((string) config('app.timezone'))->subSecond()->format('d/m');
    }
}
