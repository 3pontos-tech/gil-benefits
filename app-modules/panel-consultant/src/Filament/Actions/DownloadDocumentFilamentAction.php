<?php

namespace TresPontosTech\Consultants\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Consultants\Models\Document;

class DownloadDocumentFilamentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'download-document-action';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
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
            ->openUrlInNewTab();
    }
}
