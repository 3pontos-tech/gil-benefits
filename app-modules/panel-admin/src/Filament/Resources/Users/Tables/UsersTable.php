<?php

namespace TresPontosTech\Admin\Filament\Resources\Users\Tables;

use App\Filament\Tables\Columns\CPFColumn;
use App\Filament\Tables\Columns\RGColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use TresPontosTech\Admin\Filament\Resources\Permissions\Actions\AssignRoleAction;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('panel-admin::resources.users.table.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('panel-admin::resources.users.table.email'))
                    ->searchable(),
                CPFColumn::make('detail.tax_id')
                    ->label(__('panel-admin::resources.users.table.tax_id'))
                    ->sortable()
                    ->searchable(),
                RGColumn::make('detail.document_id')
                    ->label(__('panel-admin::resources.users.table.document_id'))
                    ->sortable()
                    ->searchable(),
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
                EditAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
                AssignRoleAction::make(),
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
