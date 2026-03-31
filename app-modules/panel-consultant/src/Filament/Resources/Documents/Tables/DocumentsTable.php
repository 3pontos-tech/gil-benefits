<?php

namespace TresPontosTech\Consultants\Filament\Resources\Documents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use TresPontosTech\Consultants\Filament\Actions\ShareDocumentFilamentAction;

class DocumentsTable
{
    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('panel-consultant::resources.documents.table.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('panel-consultant::resources.documents.table.extension_type'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('active')
                    ->label(__('panel-consultant::resources.documents.table.active'))
                    ->boolean()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
                ShareDocumentFilamentAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
