<?php

namespace TresPontosTech\PanelApp\Http\Middleware;

use App\Models\Users\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Billing\Core\Actions\ResolveBenefitAccess;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BenefitAccessEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Company\Models\Company;

readonly class RedirectUserIfNotSubscribed
{
    public function __construct(
        private ResolveBenefitAccess $resolveBenefitAccess,
        private BillingManager $billingManager,
    ) {}

    /**
     * A régua de acesso é a ResolveBenefitAccess, a mesma da API do app; aqui ficam só os efeitos
     * do painel. Quando a empresa não tem plano contratual mas está em dia, o cliente é criado nos
     * provedores de checkout, para a vitrine de planos já poder cobrar.
     *
     * Empresa sem plano nem assinatura vai para a página que explica a situação, porque o
     * colaborador não resolve a cobrança sozinho (ÉPICO 56); quem precisa de plano próprio vai
     * para a vitrine.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        /** @var User $employee */
        $employee = auth()->user();

        $access = $this->resolveBenefitAccess->for($employee, $tenant);

        if ($access === BenefitAccessEnum::PersonalPlan || $access === BenefitAccessEnum::PersonalPlanRequired) {
            collect(BillingProviderEnum::checkoutCases())
                ->each(fn (BillingProviderEnum $provider) => $this->billingManager->getDriver($provider)->ensureCustomerExists($employee));
        }

        if ($access->isGranted()) {
            return $next($request);
        }

        $route = $access === BenefitAccessEnum::CompanyPlanInactive
            ? 'filament.app.pages.company-plan-inactive'
            : 'filament.app.pages.available-subscriptions';

        if ($request->routeIs($route)) {
            return $next($request);
        }

        return to_route($route, ['tenant' => $tenant->slug]);
    }
}
