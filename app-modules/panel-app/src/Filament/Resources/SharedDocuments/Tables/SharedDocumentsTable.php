<?php

namespace TresPontosTech\App\Filament\Resources\SharedDocuments\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
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
                TextColumn::make('consultant.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Extension Type')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon(Heroicon::ArrowDown)
                    ->action(function (Document $record) {
                        $media = $record->getFirstMedia('documents');

                        return response()->streamDownload(
                            function () use ($media): void {
                                $stream = $media->stream();
                                fpassthru($stream);

                                if (is_resource($stream)) {
                                    fclose($stream);
                                }
                            },
                            $media->file_name,
                            ['Content-Type' => $media->mime_type]
                        );
                    }),
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
