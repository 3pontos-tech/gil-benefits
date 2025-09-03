<?php

namespace App\Providers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        PendingRequest::macro('withLocation', fn() => $this->withQueryParameters([
            'locationId' => config('services.highlevel.location')
        ]));

        PendingRequest::macro('withDefaultVersion', fn(?string $version = null) => $this->withHeader(
            'Version',
            $version ?? config('services.highlevel.version')
        ));

        PendingRequest::macro('withDefaultCompany', fn(?string $companyId = null) => $this->withQueryParameters(
            ['companyId' => $companyId ?? config('services.highlevel.company')]
        ));
    }
}
