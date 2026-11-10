<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Support;

use Carbon\CarbonInterface;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * Dia e hora de um encontro como os avisos escrevem ("qui., 10/09" e "10:00"), no fuso da
 * plataforma. É o único dado do encontro que o push mostra: a pessoa precisa dele para agir, e
 * ele não revela nada do que é conversado.
 */
final readonly class AppointmentMoment
{
    public function __construct(
        public string $date,
        public string $time,
    ) {}

    public static function of(Appointment $appointment): self
    {
        $at = $appointment->appointment_at->copy()->timezone((string) config('app.timezone'))->settings(['locale' => app()->getLocale()]);

        return new self($at->isoFormat('ddd., DD/MM'), $at->format('H:i'));
    }

    /**
     * Quando o encontro termina: a mesma duração que a agenda do consultor e a API usam para
     * manter o link da sala válido. Um aviso de link trocado ou de cancelamento ainda serve até lá.
     */
    public static function endOf(Appointment $appointment): CarbonInterface
    {
        return $appointment->appointment_at->copy()->addMinutes((int) config('google-calendar.default_event_duration', 60));
    }

    /**
     * @return array{date: string, time: string}
     */
    public function toReplacements(): array
    {
        return ['date' => $this->date, 'time' => $this->time];
    }
}
