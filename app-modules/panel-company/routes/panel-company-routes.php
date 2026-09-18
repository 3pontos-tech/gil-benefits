<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TresPontosTech\PanelCompany\Http\Controllers\VoucherBatchPdfController;
use TresPontosTech\PanelCompany\Http\Controllers\VoucherCodeQrController;
use TresPontosTech\PanelCompany\Support\PartnerVoucherUrls;

Route::middleware(['web', 'auth'])->prefix('partner')->group(function (): void {
    Route::get('voucher-batches/{batch}/pdf', VoucherBatchPdfController::class)
        ->name(PartnerVoucherUrls::PDF_ROUTE);

    Route::get('voucher-codes/{code}/qr', VoucherCodeQrController::class)
        ->name(PartnerVoucherUrls::QR_ROUTE);
});
