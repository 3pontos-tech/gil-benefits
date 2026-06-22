<?php

namespace TresPontosTech\Consultants\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
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
            // Only render when there is something to open: an external link or an
            // attached media file. Documents missing both would otherwise dereference
            // a null media below and 500 the whole page.
            ->visible(fn (Document $record): bool => $record->hasLink() || $record->getFirstMedia('documents') instanceof Media)
            ->url(function (Document $record): ?string {
                if ($record->hasLink()) {
                    return $record->link;
                }

                $media = $record->getFirstMedia('documents');

                if (! $media instanceof Media) {
                    return null;
                }

                return Storage::disk($media->disk)->temporaryUrl(
                    $media->getPathRelativeToRoot(),
                    now()->addMinutes(5),
                    ['ResponseContentDisposition' => 'attachment; filename="' . $media->file_name . '"'],
                );
            })
            ->openUrlInNewTab();
    }
}
