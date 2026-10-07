<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Appointments;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Api\Http\Requests\V1\Employee\ListSlotsRequest;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Support\BookableSlots;

class SlotController
{
    /**
     * Horários livres do mês, do primeiro dia elegível (hoje + BOOKING_LEAD_DAYS, ou o 1º do mês se
     * for depois) ao último, num único mapa "Y-m-d H:i:s" => "H:i". Cada dia é cacheado por
     * `api.slots_cache_seconds`, porque GetAvailableSlotsAction consulta a agenda de todos os
     * consultores por dia. Vazio sai como objeto `{}`, que é o que o contrato promete.
     *
     * Não há filtro de horários já passados: a antecedência é por dia inteiro, então nenhum slot
     * elegível pode estar no passado (paridade com o painel).
     */
    public function __invoke(ListSlotsRequest $request, BookableSlots $bookableSlots): JsonResponse
    {
        $month = Date::createFromFormat('!Y-m', $request->string('month')->toString(), (string) config('app.timezone'));
        $firstDay = $month->copy()->startOfMonth()->max(now()->addDays(Appointment::BOOKING_LEAD_DAYS)->startOfDay());
        $lastDay = $month->copy()->endOfMonth()->startOfDay();

        $slots = [];

        for ($day = $firstDay->copy(); $day->lte($lastDay); $day->addDay()) {
            $slots += Cache::remember(
                'api:slots:' . $day->toDateString(),
                (int) config('api.slots_cache_seconds'),
                fn (): array => $bookableSlots->forDay($day),
            );
        }

        return response()->json(['data' => (object) $slots]);
    }
}
