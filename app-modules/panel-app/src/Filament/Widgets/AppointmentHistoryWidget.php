<?php

namespace TresPontosTech\App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use TresPontosTech\App\Filament\Actions\CancelAppointmentAction;
use TresPontosTech\App\Filament\Actions\FeedbackAction;
use TresPontosTech\App\Filament\Actions\ViewAppointmentRecordAction;

class AppointmentHistoryWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('panel-app::widgets.appointment_history.heading'))
            ->query(
                auth()
                    ->user()
                    ->appointments()
                    ->with('feedback')
                    ->latest('appointment_at')
                    ->limit(5)
                    ->getQuery()
            )
            ->columns([
                TextColumn::make('consultant.name')
                    ->label(__('panel-app::widgets.appointment_history.consultant'))
                    ->placeholder('—'),

                TextColumn::make('category_type')
                    ->badge()
                    ->label(__('panel-app::widgets.appointment_history.category')),

                TextColumn::make('status')
                    ->badge()
                    ->label(__('panel-app::widgets.appointment_history.status')),

                TextColumn::make('appointment_at')
                    ->label(__('panel-app::widgets.appointment_history.date'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAppointmentRecordAction::make(),
                FeedbackAction::make(),
                CancelAppointmentAction::make(),
            ])
            ->paginated(false);
    }
}
