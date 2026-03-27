<?php

namespace TresPontosTech\Consultants\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Consultants\Actions\UpsertDocumentShareAction;
use TresPontosTech\Consultants\DTOs\DocumentShareDTO;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

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
            ->schema(self::getCustomForm())
            ->modalHeading('Compartilhar Documento')
            ->modalDescription('Apenas clientes que ainda não possuem acesso a este documento serão listados.')
            ->action(function (Document $record, array $data, Action $action): void {
                self::handleExecution($record, $data, $action);
            });
    }

    public static function getCustomForm(): array
    {
        return [
            Select::make('employee_id')
                ->label('Cliente')
                ->options(function (Document|DocumentShare $record) {

                    if ($record instanceof DocumentShare) {
                        $record = Document::query()->where('documents.id', $record->document_id)->firstOrFail();
                    }

                    return auth()->user()->consultant?->clients()
                        ->whereNotSharedWith($record->getKey())
                        ->pluck('users.name', 'users.id')
                        ->toArray();
                })
                ->searchable()
                ->required(),
        ];
    }

    public static function handleExecution(Document $record, array $data, $action): void
    {
        $consultantId = auth()->user()->consultant->getKey();

        $exists = DocumentShare::query()
            ->where('employee_id', $data['employee_id'])
            ->where('document_id', $record->getKey())
            ->where('consultant_id', $consultantId)
            ->exists();

        if ($exists) {
            Notification::make()->title('Enviado Anteriormente')->warning()->send();
            $action->halt();

            return;
        }

        resolve(UpsertDocumentShareAction::class)->execute(
            DocumentShareDTO::make([
                'document_id' => $record->getKey(),
                'employee_id' => $data['employee_id'],
                'consultant_id' => $consultantId,
            ])
        );

        Notification::make()->success()->title('Documento compartilhado com sucesso')->send();
    }
}
