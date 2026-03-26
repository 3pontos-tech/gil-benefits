<?php

namespace TresPontosTech\Consultants\Filament\Resources\Documents\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                CreateAction::make()
                    ->label('Share')
                    ->icon(Heroicon::Share)
                    ->schema([
                        Select::make('employee_id')
                            ->label('Cliente')
                            ->options(fn () => auth()->user()->consultant?->clients()->pluck('users.name', 'users.id')->toArray())
                            ->searchable()
                            ->required(),

                    ])->action(function (array $data): void {
                        DocumentShare::query()->updateOrCreate([
                            'document_id' => $this->getOwnerRecord()->getKey(),
                            'employee_id' => $data['employee_id'],
                            'consultant_id' => auth()->user()->consultant->getKey(),
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('active')
                    ->label(fn (DocumentShare $record): string => $record->isActive() ? 'Desativar' : 'Ativar')
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
