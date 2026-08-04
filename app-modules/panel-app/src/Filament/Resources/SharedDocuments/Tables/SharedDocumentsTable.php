<?php

namespace TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\Tables;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\PanelConsultant\Filament\Actions\DownloadDocumentFilamentAction;

class SharedDocumentsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('active', 1)
                ->whereHas('shares', fn ($subquery) => $subquery
                    ->where('employee_id', auth()->user()->getKey())
                    ->where('active', 1))
                ->with(['documentable', 'media']))
            ->heading(__('panel-app::resources.documents.shared.heading'))
            ->description(__('panel-app::resources.documents.shared.description'))
            ->columns([
                TextColumn::make('type')
                    ->label(__('panel-app::resources.documents.table.extension_type'))
                    ->badge()
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->searchable(),

                TextColumn::make('documentable.name')
                    ->label(__('panel-app::resources.documents.table.consultant'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__('panel-app::resources.documents.table.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('panel-app::resources.documents.table.created_at'))
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->recordClasses(fn (Document $record): string => 'fi-apt-doc-' . $record->type->value)
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                DownloadDocumentFilamentAction::make()
                    ->label(__('panel-app::resources.documents.actions.access'))
                    ->icon(Heroicon::OutlinedEye)
                    ->visible(fn (Document $record): bool => $record->hasLink() === false),
                Action::make('open-link')
                    ->label(__('panel-app::resources.documents.actions.access'))
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Document $record): string => $record->link)
                    ->openUrlInNewTab()
                    ->visible(fn (Document $record): bool => $record->hasLink()),
            ]);
    }
}
