<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Str;
use TresPontosTech\Vouchers\Models\VoucherBatch;

final readonly class BuildVoucherBatchPdf
{
    public const CARDS_PER_PAGE = 6;

    public function handle(VoucherBatch $batch): PdfDocument
    {
        $batch->loadMissing('company');

        $cards = $batch->codes()
            ->with('media')
            ->orderBy('code')
            ->get();

        return Pdf::loadView('vouchers::pdf.batch-cards', [
            'batch' => $batch,
            'companyName' => $batch->company->name,
            'pages' => $cards->chunk(self::CARDS_PER_PAGE),
        ])->setPaper('a4', 'portrait');
    }

    public function fileName(VoucherBatch $batch): string
    {
        return sprintf('vouchers-%s.pdf', Str::slug($batch->name));
    }
}
