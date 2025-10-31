<?php

namespace TresPontosTech\Billing\Stripe\Subscription\Company;

use Closure;
use Filament\Billing\Providers\Contracts\BillingProvider;
use Illuminate\Http\RedirectResponse;

class CompanyBillingProvider implements BillingProvider
{
    public function getRouteAction(): string|Closure|array
    {
        return function (): RedirectResponse {
            return redirect('https://pudim.com.br');
        };
    }

    public function getSubscribedMiddleware(): string
    {
        return RedirectCompanyIfNotSubscribed::class;
    }
}
