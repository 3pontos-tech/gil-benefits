<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers;

use Illuminate\Support\ServiceProvider;

class VouchersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'vouchers');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'vouchers');
    }
}
