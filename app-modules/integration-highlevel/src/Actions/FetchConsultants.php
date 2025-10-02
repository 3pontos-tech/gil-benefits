<?php

namespace TresPontosTech\IntegrationHighlevel\Actions;

use TresPontosTech\IntegrationHighlevel\HighLevelClient;

final readonly class FetchConsultants
{
    public function __construct(
        private HighLevelClient $client,
    ) {}

    public function populateAction(): array
    {
        sprintf(
            '%s_company_employees',
            config('services.highlevel.company')
        );

        //        return Cache::flexible($cacheKey, [$baseTtl, $baseTtl * 2], function () {
        return collect($this->client->getCompanyEmployees()['users'])
            ->mapWithKeys(fn ($employee): array => [$employee['id'] => $employee['name']])
            ->toArray();
        //        });
    }
}
