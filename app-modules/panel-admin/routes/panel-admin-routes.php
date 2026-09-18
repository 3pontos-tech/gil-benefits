<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TresPontosTech\PanelAdmin\Http\Controllers\VoucherBatchPdfController;
use TresPontosTech\Vouchers\Support\VoucherBatchPdfUrl;

Route::middleware(['web', 'auth'])
    ->get('admin/voucher-batches/{batch}/pdf', VoucherBatchPdfController::class)
    ->name(VoucherBatchPdfUrl::ROUTE);
