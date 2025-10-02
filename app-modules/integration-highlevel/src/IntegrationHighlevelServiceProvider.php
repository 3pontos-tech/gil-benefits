<?php

namespace TresPontosTech\IntegrationHighlevel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\ServiceProvider;

class IntegrationHighlevelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config-highlevel.php', 'highlevel');
    }

    public function boot(): void
    {
        PendingRequest::macro('withLocation', fn () => $this->withQueryParameters([
            'locationId' => config('highlevel.location'),
        ]));

        PendingRequest::macro('withDefaultVersion', fn (?string $version = null) => $this->withHeader(
            'Version',
            $version ?? config('highlevel.version')
        ));

        PendingRequest::macro('withDefaultCompany', fn (?string $companyId = null) => $this->withQueryParameters(
            ['companyId' => $companyId ?? config('highlevel.company')]
        ));
    }
}
