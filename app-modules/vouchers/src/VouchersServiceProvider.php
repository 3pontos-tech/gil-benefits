<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers;

use Illuminate\Support\ServiceProvider;
use TresPontosTech\Vouchers\Contracts\QrCodeGenerator;
use TresPontosTech\Vouchers\Support\ChillerlanQrCodeGenerator;

class VouchersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/vouchers.php', 'vouchers');

        $this->app->bind(QrCodeGenerator::class, fn (): ChillerlanQrCodeGenerator => new ChillerlanQrCodeGenerator(
            eccLevel: (string) config('vouchers.qr_code.ecc_level'),
            scale: (int) config('vouchers.qr_code.scale'),
            quietzoneSize: (int) config('vouchers.qr_code.quietzone_size'),
        ));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'vouchers');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'vouchers');
    }
}
