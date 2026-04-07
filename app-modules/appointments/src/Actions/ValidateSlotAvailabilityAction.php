<?php

namespace TresPontosTech\Appointments\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Consultants\Models\Consultant;
use Zap\Models\Schedule;

readonly class ValidateSlotAvailabilityAction
{
    /**
     * @return Collection<int, Consultant>
     */
    public function handle(CarbonInterface $appointmentAt, int $durationMinutes = 60): Collection
    {
        $date = $appointmentAt->format('Y-m-d');
        $startTime = $appointmentAt->format('H:i');
        $endTime = $appointmentAt->copy()->addMinutes($durationMinutes)->format('H:i');

        Schedule::query()
            ->where('start_date', '<=', $appointmentAt->toDateString())
            ->where('end_date', '>=', $appointmentAt->toDateString())
            ->lockForUpdate()
            ->get();

        $availableConsultants = Consultant::all()
            ->filter(fn (Consultant $consultant): bool => $consultant->isBookableAtTime($date, $startTime, $endTime));

        if ($availableConsultants->isEmpty()) {
            throw new SlotUnavailableException;
        }

        return $availableConsultants->values();
    }
}
