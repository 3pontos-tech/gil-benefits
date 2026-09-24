<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Support;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use TresPontosTech\Vouchers\Models\VoucherCode;

final class VoucherQrCodePathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        /** @var VoucherCode $code */
        $code = $media->model;

        $code->loadMissing('batch.company');

        return sprintf(
            'vouchers/%s/%s/%s/',
            Str::slug($code->batch->company->name),
            Str::slug($code->batch->name),
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
