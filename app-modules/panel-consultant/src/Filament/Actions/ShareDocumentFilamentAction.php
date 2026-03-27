<?php

namespace TresPontosTech\Consultants\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Consultants\Actions\UpsertDocumentShareAction;
use TresPontosTech\Consultants\DTOs\DocumentShareDTO;
use TresPontosTech\Consultants\Models\Document;

class ShareDocumentFilamentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'share-document-action';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->icon(Heroicon::Share)
            ->label('Compartilhar Documento')
            ->schema([
                Select::make('employee_id')
                    ->label('Cliente')
                    ->options(fn () => auth()->user()->consultant?->clients()->pluck('users.name', 'users.id')->toArray())
                    ->searchable()
                    ->required(),
            ])->action(function (Document $record, array $data): void {

                resolve(UpsertDocumentShareAction::class)->execute(
                    DocumentShareDTO::make([
                        'document_id' => $record->getKey(),
                        'employee_id' => $data['employee_id'],
                        'consultant_id' => auth()->user()->consultant->getKey(),
                    ]));

                Notification::make()
                    ->title('Documento Compartilhado com sucesso')
                    ->send();
            });
    }
}
