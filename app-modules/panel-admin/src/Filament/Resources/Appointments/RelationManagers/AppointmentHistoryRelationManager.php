<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TresPontosTech\Appointments\Models\AppointmentHistory;

class AppointmentHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('admin.name')
                    ->label('Admin')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('action_type')
                    ->label('Action Type')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('When Happened'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                $this->makeViewHistoryAction(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }

    private function makeViewHistoryAction(): ViewAction
    {
        return ViewAction::make()
            ->modalHeading(fn (AppointmentHistory $record): string => $record->action_type->getLabel())
            ->modalIcon(fn (AppointmentHistory $record): Heroicon => $record->action_type->getIcon())
            ->modalIconColor(fn (AppointmentHistory $record): array => $record->action_type->getColor())
            ->schema(fn (AppointmentHistory $record): array => $this->buildViewSchema());
    }

    /**
     * @return array<int, View>
     */
    private function buildViewSchema(): array
    {
        return [
            View::make('panel-admin::components.appointments.history-detail'),
        ];
    }
}
