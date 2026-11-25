<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Providers;

use App\Models\Users\Detail;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\Discounts\Console\FingerprintTaxIdsCommand;
use TresPontosTech\Discounts\Observers\FillTaxIdFingerprint;

final class DiscountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/discounts.php', 'discounts');
    }

    public function boot(): void
    {
        Detail::observe(FillTaxIdFingerprint::class);

        if ($this->app->runningInConsole()) {
            $this->commands([FingerprintTaxIdsCommand::class]);
        }
    }
}
