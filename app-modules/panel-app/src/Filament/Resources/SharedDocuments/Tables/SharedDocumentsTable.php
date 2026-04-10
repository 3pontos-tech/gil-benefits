<?php

namespace TresPontosTech\App\Filament\Resources\SharedDocuments\Tables;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Consultants\Models\Document;

class SharedDocumentsTable
{
    public static function table(Table $table): Table
    {

        return $table
            ->modifyQueryUsing(fn ($query) => $query?->where('active', 1)
                ->whereHas('shares', fn ($subquery) => $subquery?->where('employee_id', auth()->user()->getKey())
                    ->where('active', 1)
                ))
            ->columns([
                TextColumn::make('documentable.name')
                    ->label(__('panel-app::resources.documents.table.consultant'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__('panel-app::resources.documents.table.active'))
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
                Action::make('download')
                    ->label('Download')
                    ->icon(Heroicon::ArrowDown)
                    ->url(function (Document $record): string {
                        $media = $record->getFirstMedia('documents');

                        return Storage::temporaryUrl(
                            $media->getPath(),
                            now()->addMinutes(5),
                            ['ResponseContentDisposition' => 'attachment; filename="' . $media->file_name . '"'],
                        );
                    })
                    ->openUrlInNewTab(),
            ]);
    }
}
