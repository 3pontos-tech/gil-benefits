<?php

namespace TresPontosTech\App\Filament\Resources\SharedDocuments\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\App\Filament\Resources\SharedDocuments\Pages\EditSharedDocument;
use TresPontosTech\Consultants\Filament\Actions\DownloadDocumentFilamentAction;
use TresPontosTech\Consultants\Models\Document;

class SharedDocumentsTable
{
    public static function table(Table $table): Table
    {

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['documentable', 'media']))
            ->columns([
                TextColumn::make('documentable.name')
                    ->label(__('panel-app::resources.documents.table.consultant'))
                    ->hidden(fn ($livewire): bool => $livewire->activeTab === 'mine')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__('panel-app::resources.documents.table.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('panel-app::resources.documents.table.extension_type'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('panel-app::resources.documents.table.created_at'))
                    ->dateTime('d/m/Y')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                DownloadDocumentFilamentAction::make()
                    ->visible(fn (Document $record): bool => ! $record->hasLink()),
                Action::make('open-link')
                    ->label('Link')
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->url(fn (Document $record): string => $record->link)
                    ->openUrlInNewTab()
                    ->visible(fn (Document $record): bool => $record->hasLink()),
                EditAction::make()
                    ->visible(fn ($livewire): bool => $livewire->activeTab === 'mine'),
                DeleteAction::make()
                    ->visible(fn ($livewire): bool => $livewire->activeTab === 'mine'),
           
            ])->recordUrl(fn ($record): ?string => $record->documentable_id === auth()->user()->id ? EditSharedDocument::getUrl(['record' => $record->getKey()]) : null);
    }
}
