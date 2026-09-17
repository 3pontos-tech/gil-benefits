<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Http\Controllers;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TresPontosTech\PanelCompany\Support\PartnerVoucherAccess;
use TresPontosTech\Vouchers\Models\VoucherCode;

class VoucherCodeQrController
{
    public function __invoke(Request $request, VoucherCode $code): BinaryFileResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        $code->loadMissing('batch.company');

        abort_unless(PartnerVoucherAccess::allows($user, $code->batch->company), 403);

        $media = $code->qrCode();

        abort_if(! $media instanceof Media, 404);

        return response()->file($media->getPath());
    }
}
