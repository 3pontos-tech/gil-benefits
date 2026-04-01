<?php

namespace TresPontosTech\Consultants\Filament\Resources\Documents\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TresPontosTech\Consultants\Filament\Actions\ShareDocumentFilamentAction;
use TresPontosTech\Consultants\Models\DocumentShare;

class SharedDocumentRelationManager extends RelationManager
{
    protected static string $relationship = 'shares';

    protected static ?string $title = 'Shared With';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('employee.name'),
                TextColumn::make('created_at')
                    ->label('Shared At'),

                IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                ShareDocumentFilamentAction::make()
                    ->record($this->getOwnerRecord()),
            ])
            ->recordActions([
                EditAction::make()
                    ->schema(ShareDocumentFilamentAction::getCustomForm())
                    ->action(fn (DocumentShare $record, array $data, Action $action) => ShareDocumentFilamentAction::handleExecution($record->document, $data, $action)),
                Action::make('active')
                    ->label(fn (DocumentShare $record): string => $record->isActive() ? 'Desativar' : 'Ativar')
                    ->icon(fn (DocumentShare $record): Heroicon => $record->isActive() ? Heroicon::XCircle : Heroicon::CheckCircle)
                    ->color(fn (DocumentShare $record): mixed => $record->isActive() ? Color::Red : Color::Green)
                    ->action(fn (DocumentShare $record) => $record->isActive() ? $record->deactivate() : $record->activate()),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
