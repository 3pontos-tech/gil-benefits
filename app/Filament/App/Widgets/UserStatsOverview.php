<?php

namespace App\Filament\App\Widgets;

use App\Enums\VoucherStatusEnum;
use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            $this->mountAvailableHours(),
            $this->mountAvailableVouchers(),
            $this->mountNextAppointment(),
            //            $this->mountNextAppointment(),
        ];
    }

    private function mountAvailableVouchers(): Stat
    {
        $tenant = filament()->getTenant()->load('vouchers');

        $availableVouchers = $tenant->vouchers()
            ->where('status', VoucherStatusEnum::Active)
            ->where('valid_until', '>=', now())
            ->count();

        return Stat::make('Available Vouchers', $availableVouchers)
            ->description('Available for booking')
            ->icon('heroicon-s-ticket');
    }

    private function mountAvailableHours(): Stat
    {
        $tenant = filament()->getTenant()->load('vouchers');

        $availableVouchers = $tenant->vouchers()
            ->where('status', VoucherStatusEnum::Active)
            ->where('valid_until', '>=', now())
            ->count();

        return Stat::make('Hours Available', $availableVouchers)
            ->icon('heroicon-s-clock')
            ->description('From active vouchers');
    }

    private function mountNextAppointment(): Stat
    {

        $appointment = Appointment::query()->where('user_id', auth()->id())->where('date', '>=', now())->first();
        $consultant = $appointment->consultant->name ?? 'Make an appointment';
        $appointmentDate = $appointment->date ?? null;
        $description = "{$consultant} - {$appointmentDate}";

        return Stat::make('Next Appointment', $appointment ?? 'N/A')
            ->icon('heroicon-s-calendar')
            ->description($description);
    }
}
