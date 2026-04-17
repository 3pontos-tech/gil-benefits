<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
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

    public function getSharedDocuments(): Collection
    {
        $record = $this->getRecord();

        return Document::query()
            ->whereHas('shares', function ($query) use ($record): void {
                $query->where('employee_id', $record->user_id)
                    ->where('active', 1);
            })
            ->with(['media', 'documentable'])
            ->get();
    }

    public function downloadDocumentAction(): Action
    {
        return Action::make('downloadDocument')
            ->iconButton()
            ->icon(Heroicon::ArrowDownTray)
            ->tooltip('Download')
            ->color('gray')
            ->visible(fn (array $arguments): bool => $this->resolveEmployeeDocumentMedia($arguments['documentId'] ?? null) instanceof Media)
            ->url(function (array $arguments): ?string {
                $media = $this->resolveEmployeeDocumentMedia($arguments['documentId'] ?? null);

                return $media instanceof Media ? $this->buildDownloadUrl($media) : null;
            }, shouldOpenInNewTab: true);
    }

    public function downloadSharedDocumentAction(): Action
    {
        return Action::make('downloadSharedDocument')
            ->iconButton()
            ->icon(Heroicon::ArrowDownTray)
            ->tooltip('Download')
            ->color('gray')
            ->visible(fn (array $arguments): bool => $this->resolveSharedDocumentMedia($arguments['documentId'] ?? null) instanceof Media)
            ->url(function (array $arguments): ?string {
                $media = $this->resolveSharedDocumentMedia($arguments['documentId'] ?? null);

                return $media instanceof Media ? $this->buildDownloadUrl($media) : null;
            }, shouldOpenInNewTab: true);
    }

    private function resolveEmployeeDocumentMedia(?string $documentId): ?Media
    {
        if (blank($documentId)) {
            return null;
        }

        $document = Document::query()
            ->where('documentable_id', $this->getRecord()->user_id)
            ->where('documentable_type', 'users')
            ->find($documentId);

        return $document?->getFirstMedia('documents');
    }

    private function resolveSharedDocumentMedia(?string $documentId): ?Media
    {
        if (blank($documentId)) {
            return null;
        }

        $appointment = $this->getRecord();

        $document = Document::query()
            ->whereHas('shares', function ($query) use ($appointment): void {
                $query->where('employee_id', $appointment->user_id)
                    ->where('active', 1);
            })
            ->find($documentId);

        return $document?->getFirstMedia('documents');
    }

    private function buildDownloadUrl(Media $media): string
    {
        return Storage::disk($media->disk)->temporaryUrl(
            $media->getPathRelativeToRoot(),
            now()->addMinutes(5),
            ['ResponseContentDisposition' => sprintf('attachment; filename="%s"', $media->file_name)],
        );
    }
}
