<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Appointments;

use Illuminate\Http\JsonResponse;
use TresPontosTech\Api\Actions\V1\Employee\ListBookableSlotsAction;
use TresPontosTech\Api\Http\Requests\V1\Employee\ListSlotsRequest;

class SlotController
{
    /**
     * Horários livres do mês, com chaves ISO 8601 e offset. Vazio sai como objeto `{}`, que é o que o
     * contrato promete.
     */
    public function __invoke(ListSlotsRequest $request, ListBookableSlotsAction $listSlots): JsonResponse
    {
        $month = $request->date('month', '!Y-m', (string) config('app.timezone'));

        return response()->json(['data' => (object) $listSlots->handle($month)]);
    }
}
