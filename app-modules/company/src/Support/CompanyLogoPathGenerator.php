<?php

namespace TresPontosTech\Company\Support;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use TresPontosTech\Company\Models\Company;

final class CompanyLogoPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        /** @var Company $company */
        $company = $media->model;

        $folderName = Str::slug($company->name);

        return sprintf('empresa/%s/', $folderName);
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
