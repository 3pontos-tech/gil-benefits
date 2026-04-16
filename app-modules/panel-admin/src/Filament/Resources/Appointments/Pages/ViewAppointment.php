<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Admin\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Consultants\Models\Document;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    protected string $view = 'panel-admin::filament.resources.appointments.pages.view-appointment';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function getEmployeeDocuments(): Collection
    {
        $record = $this->getRecord();

        return Document::query()
            ->where('documentable_id', $record->user_id)
            ->where('documentable_type', 'users')
            ->get();
    }

    public function downloadDocument(string $documentId): void
    {
        $document = Document::find($documentId);

        if (! $document) {
            return;
        }

        $media = $document->getFirstMedia('documents');

        if (! $media) {
            return;
        }

        $url = Storage::disk($media->disk)->temporaryUrl(
            $media->getPathRelativeToRoot(),
            now()->addMinutes(5),
            ['ResponseContentDisposition' => "attachment; filename=\"{$media->file_name}\""],
        );

        $this->js("window.open('{$url}', '_blank')");
    }
}
