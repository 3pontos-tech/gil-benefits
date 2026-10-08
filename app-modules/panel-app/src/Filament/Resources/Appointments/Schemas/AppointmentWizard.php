<?php

namespace TresPontosTech\PanelApp\Filament\Resources\Appointments\Schemas;

use Illuminate\Support\Facades\Date;
use Throwable;
use TresPontosTech\Appointments\Support\BookableSlots;

class AppointmentWizard
{
    /**
     * @return array<string, string>
     */
    public static function availableSlots(?string $date): array
    {
        if (blank($date)) {
            return [];
        }

        try {
            $startDate = Date::parse($date);
        } catch (Throwable) {
            // A data chega do estado do cliente; lixo vira lista vazia em vez
            // de um 500 do Livewire.
            return [];
        }

        return resolve(BookableSlots::class)->forDay($startDate);
    }
}
