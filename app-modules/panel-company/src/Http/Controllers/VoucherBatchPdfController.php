<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Http\Controllers;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TresPontosTech\PanelCompany\Support\PartnerVoucherAccess;
use TresPontosTech\Vouchers\Models\VoucherBatch;

class VoucherBatchPdfController
{
    public function __invoke(Request $request, VoucherBatch $batch): BinaryFileResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        $batch->loadMissing('company');

        abort_unless(PartnerVoucherAccess::allows($user, $batch->company), 403);

        $media = $batch->pdf();

        abort_if(! $media instanceof Media, 404);

        return response()->download($media->getPath(), $media->file_name);
    }
}
