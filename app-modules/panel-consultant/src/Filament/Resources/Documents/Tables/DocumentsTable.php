<?php

namespace TresPontosTech\Consultants\Filament\Resources\Documents\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use TresPontosTech\Consultants\Document;
use TresPontosTech\Consultants\DocumentShare;

class DocumentsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('registerMediaConversionsUsingModelInstance'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
                Action::make('share')
                    ->icon(Heroicon::Share)
                    ->schema([
                        Select::make('client_id')
                            ->label('Cliente')
                            ->options(fn () => auth()->user()->consultant?->clients()->pluck('users.name', 'users.id')->toArray())

                            ->searchable()
                            ->required(),
                    ])->action(function (Document $record, array $data): void {
                        DocumentShare::query()->updateOrCreate([
                            'document_id' => $record->getKey(),
                            'employee_id' => $data['client_id'],
                            'consultant_id' => auth()->user()->consultant->getKey(),
                        ]);
                        Notification::make('fuedase')
                            ->title('aoo potencia')
                            ->send();
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
