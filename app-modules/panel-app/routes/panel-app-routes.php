<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TresPontosTech\Discounts\Support\DiscountInviteUrl;
use TresPontosTech\PanelApp\Http\Controllers\DiscountInviteController;

Route::middleware('web')->group(function (): void {
    Route::get('convite-clau/sem-desconto', [DiscountInviteController::class, 'leave'])->name(DiscountInviteUrl::LEAVE_ROUTE);
    Route::get('convite-clau/{grant}', [DiscountInviteController::class, 'show'])->whereUuid('grant')->name(DiscountInviteUrl::ROUTE);
});
