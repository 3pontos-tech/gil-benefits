<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TresPontosTech\IntegrationClau\Http\Controllers\DiscountReservationController;
use TresPontosTech\IntegrationClau\Http\Middleware\ValidateClauSignature;

Route::post('api/clau/v1/discount-reservations', DiscountReservationController::class)
    ->name('clau.discount-reservations.store')
    ->middleware(['throttle:60,1', ValidateClauSignature::class]);
