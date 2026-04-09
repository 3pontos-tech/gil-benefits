<?php

namespace TresPontosTech\Consultants\Support;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use TresPontosTech\Consultants\Models\Consultant;

class DocumentPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {

        $owner = $media->model->documentable;

        if (! $owner) {
            $owner = auth()->user()?->consultant ?? auth()->user();
        }

        $folderName = Str::slug($owner->name ?? $owner->user->name);

        if ($owner instanceof Consultant) {
            return sprintf('consultores/%s/%s/', $folderName, $media->id);
        }

        return sprintf('cliente/%s/%s/', $folderName, $media->id);
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'responsive/';
    }
}
