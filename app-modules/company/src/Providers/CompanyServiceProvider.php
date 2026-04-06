<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\Company\Models\Company;

class CompanyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Relation::morphMap([
            'company' => Company::class,
        ]);
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/flamma.php', 'company');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'companies');
    }
}
