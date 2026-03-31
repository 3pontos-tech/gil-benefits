<?php

namespace TresPontosTech\Consultants\Observers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Models\Document;

class MediaObserver
{
    public function created(Media $media): void
    {
        if ($media->model_type !== 'documents') {
            return;
        }

        if ($media->collection_name !== 'documents') {
            return;
        }

        $type = DocumentExtensionTypeEnum::tryFrom($media->extension)?->value;

        if (! $type) {
            return;
        }

        Document::query()->where('id', $media->model_id)
            ->update(['type' => $type]);
    }
}
