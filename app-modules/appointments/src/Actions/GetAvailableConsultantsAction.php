<?php

namespace TresPontosTech\Appointments\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use TresPontosTech\Consultants\Models\Consultant;

readonly class GetAvailableConsultantsAction
{
    /**
     * @return Collection<int, Consultant>
     */
    public function handle(
        CarbonInterface $appointmentAt,
        int $durationMinutes = 60,
        ?string $alwaysIncludeConsultantId = null,
    ): Collection {
        $date = $appointmentAt->format('Y-m-d');
        $startTime = $appointmentAt->format('H:i');
        $endTime = $appointmentAt->copy()->addMinutes($durationMinutes)->format('H:i');

        return Consultant::all()
            ->filter(function (Consultant $consultant) use ($date, $startTime, $endTime, $alwaysIncludeConsultantId): bool {
                if ($alwaysIncludeConsultantId !== null && $consultant->getKey() === $alwaysIncludeConsultantId) {
                    return true;
                }

                return $consultant->isBookableAtTime($date, $startTime, $endTime);
            })
            ->values();
    }
}
