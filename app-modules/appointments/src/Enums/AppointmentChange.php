<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Enums;

use TresPontosTech\Appointments\DTO\AppointmentSnapshot;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * O que a equipe mudou num encontro já confirmado, do mais grave para o menos (#299, casos 3 e 4).
 * Um salvamento costuma fazer mais de uma coisa: trocar o horário limpa o consultor, e o encontro
 * volta para "A confirmar". O aviso conta só a mais grave, porque é o que a pessoa precisa saber.
 *
 * O horário vem antes do consultor: o admin pode trocar os dois no mesmo save, e quem não vê o
 * horário novo perde o encontro. O consultor e o link novos aparecem na tela do encontro, que é
 * por onde a pessoa entra na sala.
 */
enum AppointmentChange: string
{
    case Unconfirmed = 'unconfirmed';
    case Time = 'time';
    case Consultant = 'consultant';
    case Link = 'link';

    /**
     * Compara o encontro salvo com o que ele era antes. Encontro que não estava confirmado não
     * gera aviso: a pessoa ainda espera a confirmação, e é ela que avisa.
     */
    public static function between(AppointmentSnapshot $before, Appointment $after): ?self
    {
        if ($before->status !== AppointmentStatus::Active) {
            return null;
        }

        return match (true) {
            $after->status !== AppointmentStatus::Active => self::Unconfirmed,
            ! $after->appointment_at->equalTo($before->appointmentAt) => self::Time,
            $after->consultant_id !== $before->consultantId => self::Consultant,
            $after->meeting_url !== $before->meetingUrl => self::Link,
            default => null,
        };
    }
}
