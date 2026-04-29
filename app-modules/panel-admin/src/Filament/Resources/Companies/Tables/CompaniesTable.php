<?php

namespace TresPontosTech\Admin\Filament\Resources\Companies\Tables;

use App\Filament\Tables\Columns\CnpjColumn;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use TresPontosTech\Company\Models\Company;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('owner.name')
                    ->label(__('panel-admin::resources.companies.table.owner'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('panel-admin::resources.companies.table.name'))
                    ->searchable(),
                CnpjColumn::make('tax_id')
                    ->label(__('panel-admin::resources.companies.table.tax_id'))
                    ->searchable(),
                TextColumn::make('plans.name')
                    ->label(__('panel-admin::resources.companies.table.plan')),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('manage_company')
                    ->label(__('panel-admin::resources.companies.actions.manage'))
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->color('info')
                    ->visible(fn (Company $record): bool => ! $record->trashed())
                    ->url(fn (Company $record): string => route('filament.company.pages.dashboard', ['tenant' => $record->slug]))
                    ->openUrlInNewTab(),
                EditAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
