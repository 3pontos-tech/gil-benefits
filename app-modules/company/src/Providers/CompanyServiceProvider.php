<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Providers;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
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
        $this->defineGate();
        $this->mergeConfigFrom(__DIR__ . '/../../config/flamma.php', 'company');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'companies');
    }

    private function defineGate(): void
    {
        Gate::define('register_company', function (User $user) {

            if ($user->isAdmin() || $user->isCompanyOwner()) {
                return true;
            }

            $registrationUrl = route('filament.company.tenant.registration');
            $referer = request()->header('referer');

            if (request()->routeIs('filament.company.tenant.registration') || $referer === $registrationUrl || request()->is('company')) {
                return true;
            }
        });
    }
}
