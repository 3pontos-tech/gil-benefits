<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Filament\Resources\Documents\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TresPontosTech\Consultants\Filament\Actions\ShareDocumentFilamentAction;
use TresPontosTech\Consultants\Models\DocumentShare;

class SharedDocumentRelationManager extends RelationManager
{
    protected static string $relationship = 'shares';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('panel-consultant::resources.share_documents.relation_manager.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('panel-consultant::resources.share_documents.relation_manager.table.employee')),
                TextColumn::make('created_at')
                    ->label(__('panel-consultant::resources.share_documents.relation_manager.table.shared_at')),

                IconColumn::make('active')
                    ->label(__('panel-consultant::resources.share_documents.relation_manager.table.active'))
                    ->boolean()
                    ->searchable()
                    ->sortable(),
            ])
            ->headerActions([
                ShareDocumentFilamentAction::make()
                    ->record($this->getOwnerRecord()),
            ])
            ->recordActions([
                Action::make('active')
                    ->label(fn (DocumentShare $record): string => $record->isActive()
                        ? __('panel-consultant::resources.share_documents.relation_manager.actions.deactivate')
                        : __('panel-consultant::resources.share_documents.relation_manager.actions.activate')
                    )
                    ->icon(fn (DocumentShare $record): Heroicon => $record->isActive() ? Heroicon::XCircle : Heroicon::CheckCircle)
                    ->color(fn (DocumentShare $record): mixed => $record->isActive() ? Color::Red : Color::Green)
                    ->action(fn (DocumentShare $record) => $record->isActive() ? $record->deactivate() : $record->activate()),
                DeleteAction::make(),
                RestoreAction::make(),
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
