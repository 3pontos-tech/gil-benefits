<?php

namespace App\Action\HighLevel;

use App\Clients\HighLevelClient;
use Illuminate\Support\Facades\Cache;

readonly class FetchConsultants
{
    public function __construct(
        private HighLevelClient $client,
    ) {}

    public function populateAction(): array
    {
        $baseTtl = 60 * 60;
        $cacheKey = sprintf(
            '%s_company_employees',
            config('services.highlevel.company')
        );

        //        return Cache::flexible($cacheKey, [$baseTtl, $baseTtl * 2], function () {
        return collect($this->client->getCompanyEmployees()['users'])
            ->mapWithKeys(fn ($employee) => [$employee['id'] => $employee['name']])
            ->toArray();
        //        });
    }
}
