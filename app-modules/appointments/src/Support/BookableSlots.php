<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Throwable;
use TresPontosTech\Appointments\Actions\GetAvailableSlotsAction;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * Horários que o colaborador pode escolher: a disponibilidade dos consultores (GetAvailableSlotsAction)
 * recortada pela antecedência mínima de BOOKING_LEAD_DAYS, aplicada por dia inteiro.
 *
 * Tudo é comparado no fuso da aplicação: as chaves da disponibilidade são "Y-m-d H:i:s" nesse fuso,
 * então um horário recebido com outro offset (o app envia ISO em UTC) é convertido antes de comparar.
 */
final readonly class BookableSlots
{
    public function __construct(private GetAvailableSlotsAction $availableSlots) {}

    /**
     * Horários agendáveis do dia, no formato de GetAvailableSlotsAction ("Y-m-d H:i:s" => "H:i").
     * Vazio quando o dia é anterior a hoje + BOOKING_LEAD_DAYS.
     *
     * @return array<string, string>
     */
    public function forDay(CarbonInterface $day): array
    {
        $day = $this->inAppTimezone($day);

        if ($day->copy()->startOfDay()->lt(now()->addDays(Appointment::BOOKING_LEAD_DAYS)->startOfDay())) {
            return [];
        }

        return $this->availableSlots->handle($day);
    }

    /**
     * Se o horário está na lista que o próprio produto oferece para aquele dia.
     */
    public function contains(CarbonInterface $slotAt): bool
    {
        $slotAt = $this->inAppTimezone($slotAt);

        return array_key_exists($slotAt->toDateTimeString(), $this->forDay($slotAt));
    }

    /**
     * Interpreta o horário como chega do cliente e devolve-o já no fuso da aplicação, ou null
     * quando está vazio, não é uma data ou não é um horário agendável.
     */
    public function parse(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            $slotAt = $this->inAppTimezone(Date::parse($value));
        } catch (Throwable) {
            return null;
        }

        return $this->contains($slotAt) ? $slotAt : null;
    }

    private function inAppTimezone(CarbonInterface $moment): Carbon
    {
        return Date::instance($moment)->setTimezone((string) config('app.timezone'));
    }
}
