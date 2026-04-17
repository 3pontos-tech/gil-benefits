<?php

namespace TresPontosTech\Consultants\Filament\Actions;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Consultants\Actions\UpsertDocumentShareAction;
use TresPontosTech\Consultants\DTOs\DocumentShareDTO;
use TresPontosTech\Consultants\Mail\DocumentSharedMail;
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
            ->label(__('panel-consultant::resources.share_documents.action.label'))
            ->schema(self::getCustomForm())
            ->modalHeading(__('panel-consultant::resources.share_documents.action.heading'))
            ->modalDescription(__('panel-consultant::resources.share_documents.action.modal_description'))
            ->action(function (Document $record, array $data, Action $action): void {
                self::handleExecution($record, $data, $action);
            });
    }

    public static function getCustomForm(): array
    {
        return [
            Select::make('employee_id')
                ->label(__('panel-consultant::resources.share_documents.action.form.customer'))
                ->options(function (Document|DocumentShare $record) {

                    if ($record instanceof DocumentShare) {
                        $record = Document::query()->where('documents.id', $record->document_id)->firstOrFail();
                    }

                    return auth()->user()->consultant?->clients()
                        ->whereNotSharedWith($record->getKey())
                        ->pluck('users.name', 'users.id')
                        ->toArray();
                })
                ->getOptionLabelUsing(fn ($value) => auth()->user()->consultant?->clients()->where('users.id', $value)->first()?->name)
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

        $employee = User::query()->find($data['employee_id']);

        if ($employee) {
            Mail::to($employee->email)->queue(new DocumentSharedMail($record, $employee));
        }

        Notification::make()->success()->title('Documento compartilhado com sucesso')->send();
    }
}
