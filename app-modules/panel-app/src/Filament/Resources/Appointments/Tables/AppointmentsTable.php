<?php

namespace TresPontosTech\PanelApp\Filament\Resources\Appointments\Tables;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Actions\CancelAppointmentAction;
use TresPontosTech\PanelApp\Filament\Actions\FeedbackAction;
use TresPontosTech\PanelApp\Filament\Actions\RescheduleAppointmentAction;
use TresPontosTech\PanelApp\Filament\Actions\ViewAppointmentRecordAction;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\Pages\ListAppointments;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('user_id', auth()->user()->getKey())
                ->with(['consultant', 'feedback', 'record'])
            )
            ->heading(__('panel-app::resources.appointments.table.heading'))
            ->description(__('panel-app::resources.appointments.table.description'))
            ->headerActions([
                Action::make('new-appointment')
                    ->label(__('panel-app::resources.appointments.schedule.action_label'))
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->disabled(fn (): bool => ! auth()->user()->canCreateAppointment())
                    ->action(fn (ListAppointments $livewire) => $livewire->replaceMountedAction('scheduleAppointment')),
            ])
            ->columns([
                TextColumn::make('consultant.name')
                    ->label(__('appointments::resources.appointments.table.columns.consultant'))
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('category_type')
                    ->label(__('panel-app::resources.appointments.table.category_type'))
                    ->badge()
                    ->extraCellAttributes(fn (Appointment $record): array => [
                        'class' => 'fi-apt-cat-' . str_replace('_', '-', $record->category_type->value),
                    ])
                    ->searchable(),
                TextColumn::make('appointment_at')
                    ->label(__('appointments::resources.appointments.table.columns.appointment_at'))
                    ->dateTime('d/m/Y - H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('appointments::resources.appointments.table.columns.status'))
                    ->badge()
                    ->extraCellAttributes(fn (Appointment $record): array => [
                        'class' => 'fi-apt-status-' . str_replace('_', '-', $record->status->value),
                    ])
                    ->searchable(),
            ])
            ->defaultSort('appointment_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('panel-app::resources.appointments.table.filters.status'))
                    ->options(AppointmentStatus::class)
                    ->multiple(),
                SelectFilter::make('category_type')
                    ->label(__('panel-app::resources.appointments.table.filters.category_type'))
                    ->options(AppointmentCategoryEnum::class)
                    ->multiple(),
            ])
            ->recordActions([
                ViewAppointmentRecordAction::make(),
                FeedbackAction::make(),
                RescheduleAppointmentAction::make(),
                CancelAppointmentAction::make(),
            ]);
    }
}
