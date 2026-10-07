<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Api\Support\ApiDates;
use TresPontosTech\Appointments\Support\BookableSlots;

final readonly class ListBookableSlotsAction
{
    public function __construct(private BookableSlots $bookableSlots) {}

    /**
     * Horários livres do mês, do primeiro dia agendável (BookableSlots::firstBookableDay(), ou o 1º
     * do mês se for depois) ao último, num único mapa "instante ISO 8601 com offset do fuso da
     * aplicação" => "H:i". O app ecoa a chave no POST/PATCH. Cada dia é cacheado por
     * `api.slots_cache_seconds`, porque a disponibilidade consulta a agenda de todos os consultores.
     *
     * @return array<string, string>
     */
    public function handle(CarbonInterface $month): array
    {
        $timezone = (string) config('app.timezone');
        $firstDay = Date::instance($month)->setTimezone($timezone)->startOfMonth()->max($this->bookableSlots->firstBookableDay());
        $lastDay = Date::instance($month)->setTimezone($timezone)->endOfMonth()->startOfDay();

        $slots = [];

        for ($day = $firstDay->copy(); $day->lte($lastDay); $day->addDay()) {
            $daySlots = Cache::remember(
                'api:slots:' . $day->toDateString(),
                (int) config('api.slots_cache_seconds'),
                fn (): array => $this->bookableSlots->forDay($day),
            );

            foreach ($daySlots as $startsAt => $label) {
                $slots[(string) ApiDates::dateTime(Date::parse($startsAt, $timezone))] = $label;
            }
        }

        return $slots;
    }
}
