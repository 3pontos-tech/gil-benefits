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
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TresPontosTech\Consultants\Actions\UpsertDocumentShareAction;
use TresPontosTech\Consultants\DTOs\DocumentShareDTO;
use TresPontosTech\Consultants\Models\Consultant;
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
                    ->schema($this->contentSchema())
                    ->action(fn (array $data, Action $action) => $this->updateOrCreateAction($data, $action)),
            ])
            ->recordActions([
                EditAction::make()
                    ->schema($this->contentSchema())
                    ->action(fn (array $data, Action $action) => $this->updateOrCreateAction($data, $action)),
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

    private function contentSchema(): array
    {
        return [
            Select::make('employee_id')
                ->label('Cliente')
                ->options(function () {
                    /** @var Consultant $consultant */
                    $consultant = auth()->user()->consultant;

                    return $consultant->clients()
                        ->whereNotSharedWith($this->getOwnerRecord()->getKey())
                        ->pluck('users.name', 'users.id')->toArray();
                })
                ->searchable()
                ->required(),
        ];
    }

    private function updateOrCreateAction(array $data, Action $action): void
    {
        $exists = DocumentShare::query()
            ->where('employee_id', $data['employee_id'])
            ->where('document_id', $this->getOwnerRecord()->getKey())
            ->where('consultant_id', auth()->user()->consultant->getKey())
            ->exists();

        if ($exists) {
            Notification::make('already-sent')
                ->title('Enviado Anteriormente')
                ->body('Documento já enviado anteriormente para este cliente')
                ->warning()
                ->send();

            $action->halt();
        }

        resolve(UpsertDocumentShareAction::class)->execute(
            DocumentShareDTO::make([
                'document_id' => $this->getOwnerRecord()->getKey(),
                'employee_id' => $data['employee_id'],
                'consultant_id' => auth()->user()->consultant->getKey(),
            ])
        );
    }
}
