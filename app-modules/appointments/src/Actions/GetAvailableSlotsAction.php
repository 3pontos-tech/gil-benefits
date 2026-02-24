<?php

namespace TresPontosTech\Appointments\Actions;

use Illuminate\Support\Carbon;
use TresPontosTech\Consultants\Models\Consultant;

readonly class GetAvailableSlotsAction
{
    public function handle(Carbon $date, int $durationMinutes = 60, int $bufferMinutes = 0): array
    {
        $consultants = Consultant::all();
        $dateString = $date->format('Y-m-d');

        $allSlots = collect();

        foreach ($consultants as $consultant) {
            $slots = $consultant->getBookableSlots($dateString, $durationMinutes, $bufferMinutes);

            $bookable = collect($slots)
                ->filter(fn (array $slot): bool => $slot['is_available'])
                ->pluck('start_time');

            $allSlots = $allSlots->merge($bookable);
        }

        return $allSlots
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(fn (string $time): array => [
                $date->copy()->setTimeFromTimeString($time)->toDateTimeString() => $time,
            ])
            ->all();
    }
}
