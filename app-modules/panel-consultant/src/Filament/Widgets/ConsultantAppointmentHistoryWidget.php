<?php

namespace TresPontosTech\Consultants\Filament\Widgets;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use TresPontosTech\Consultants\Filament\Resources\Appointments\Tables\AppointmentsTable;
use TresPontosTech\Consultants\Models\Consultant;

class ConsultantAppointmentHistoryWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('panel-app::widgets.appointment_history.heading');
    }

    public function table(Table $table): Table
    {
        $consultant = Consultant::query()->where('consultants.user_id', auth()->user()->getKey())->first();

        return $table
            ->query(
                $consultant->appointments()
                    ->latest('appointment_at')
                    ->limit(5)
                    ->getQuery()
            )
            ->columns(AppointmentsTable::columns())
            ->paginated(false);
    }
}
