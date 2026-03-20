<?php

namespace TresPontosTech\PanelCompany\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestTenantAdoptorsTableWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 19;

    public function table(Table $table): Table
    {
        return $table
            ->searchable(false)
            ->heading(__('panel-company::widgets.latest_adoptors.heading'))
            ->query(fn (): Builder => Filament::getTenant()
                ->employees()
                ->take(5)
                ->getQuery())
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label(__('panel-company::widgets.latest_adoptors.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('panel-company::widgets.latest_adoptors.email'))
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->label(__('panel-company::widgets.latest_adoptors.email_verified_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('crm_id')
                    ->label(__('panel-company::widgets.latest_adoptors.external_id'))
                    ->searchable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('stripe_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pm_type')
                    ->label(__('panel-company::widgets.latest_adoptors.payment_method'))
                    ->searchable(),
                TextColumn::make('pm_last_four')
                    ->label(__('panel-company::widgets.latest_adoptors.last_four_digits'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('trial_ends_at')
                    ->label(__('panel-company::widgets.latest_adoptors.trial_ends_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
