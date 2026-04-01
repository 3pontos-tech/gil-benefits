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
        /** @var Consultant $consultant */
        $consultant = $media->model?->consultant;

        $folderName = Str::slug($consultant->name ?? $consultant->user->name);

        return sprintf('consultores/%s/%s/', $folderName, $media->id);
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
