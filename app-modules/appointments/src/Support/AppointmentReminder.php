<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Support;

use Carbon\CarbonInterface;

/**
 * A regra do lembrete de 1 h antes, num lugar só. Quem acabou de dizer o horário à pessoa (a
 * confirmação, uma mudança feita pela equipe, ou ela mesma ao remarcar) já fez o papel do lembrete
 * quando o encontro começa dentro da próxima hora; fora dela, o lembrete volta a valer.
 */
final class AppointmentReminder
{
    public const int MINUTES_BEFORE = 60;

    /**
     * O valor de `appointments.reminder_sent_at` logo depois de a pessoa saber o horário.
     */
    public static function stampAfterTellingTheTime(CarbonInterface $appointmentAt): ?CarbonInterface
    {
        return $appointmentAt->lessThanOrEqualTo(now()->addMinutes(self::MINUTES_BEFORE)) ? now() : null;
    }
}
