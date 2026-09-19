<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Support;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use TresPontosTech\Vouchers\Models\VoucherBatch;

final class VoucherBatchPdfPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        /** @var VoucherBatch $batch */
        $batch = $media->model;

        $batch->loadMissing('company');

        return sprintf(
            'vouchers/%s/%s/pdf/%s/',
            Str::slug($batch->company->name),
            Str::slug($batch->name),
            $media->id,
        );
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
