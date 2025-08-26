<?php

namespace App\Filament\App\Widgets;

use App\Models\Appointment;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingAppointments extends TableWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = Appointment::query()
            ->where('appointments.user_id', auth()->user()->id)
            ->where('date', '>=', now())
            ->orderBy('date');

        return $table->query(fn (): Builder => $query)
            ->columns([
                TextColumn::make('consultant.name')
                    ->searchable(),
                TextColumn::make('voucher.id')
                    ->searchable(),
                TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
