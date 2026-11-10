<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Enums;

use Carbon\CarbonInterface;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * O que a equipe mudou num encontro já confirmado, do mais grave para o menos (#299, casos 3 e 4).
 * Um salvamento costuma fazer mais de uma coisa: trocar o horário limpa o consultor, e o encontro
 * volta para "A confirmar". O aviso conta só a mais grave, porque é o que a pessoa precisa saber.
 */
enum AppointmentChange: string
{
    case Unconfirmed = 'unconfirmed';
    case Consultant = 'consultant';
    case Time = 'time';
    case Link = 'link';

    /**
     * Compara o encontro salvo com o que ele era antes. Encontro que não estava confirmado não
     * gera aviso: a pessoa ainda espera a confirmação, e é ela que avisa.
     */
    public static function between(
        AppointmentStatus $previousStatus,
        ?string $previousConsultantId,
        CarbonInterface $previousAppointmentAt,
        ?string $previousMeetingUrl,
        Appointment $appointment,
    ): ?self {
        if ($previousStatus !== AppointmentStatus::Active) {
            return null;
        }

        return match (true) {
            $appointment->status !== AppointmentStatus::Active => self::Unconfirmed,
            $appointment->consultant_id !== $previousConsultantId => self::Consultant,
            ! $appointment->appointment_at->equalTo($previousAppointmentAt) => self::Time,
            $appointment->meeting_url !== $previousMeetingUrl => self::Link,
            default => null,
        };
    }
}
