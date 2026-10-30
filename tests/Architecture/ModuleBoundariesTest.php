<?php

declare(strict_types=1);

use TresPontosTech\Billing\BillingServiceProvider;
use TresPontosTech\Billing\Core\Actions\ResolveQuotaAllowance;
use TresPontosTech\Tenant\Models\Traits\HasTenant;
use TresPontosTech\User\Concerns\ChecksImportCompletion;

$domain = [
    'TresPontosTech\Appointments',
    'TresPontosTech\Billing',
    'TresPontosTech\Company',
    'TresPontosTech\Consultants',
    'TresPontosTech\Credits',
    'TresPontosTech\Permissions',
    'TresPontosTech\Support',
    'TresPontosTech\Tenant',
    'TresPontosTech\User',
    'TresPontosTech\Vouchers',
];

$integration = [
    'TresPontosTech\IntegrationBarte',
    'TresPontosTech\IntegrationGoogleCalendar',
    'TresPontosTech\IntegrationHighlevel',
    'TresPontosTech\IntegrationMonday',
    'TresPontosTech\IntegrationVirtu',
];

$presentation = [
    'TresPontosTech\PanelAdmin',
    'TresPontosTech\PanelApp',
    'TresPontosTech\PanelCompany',
    'TresPontosTech\PanelConsultant',
    'TresPontosTech\Api',
];

$filamentUi = [
    'Filament\Actions',
    'Filament\Billing',
    'Filament\Facades',
    'Filament\Forms',
    'Filament\Infolists',
    'Filament\Notifications',
    'Filament\Pages',
    'Filament\Panel',
    'Filament\Resources',
    'Filament\Schemas',
    'Filament\Tables',
    'Filament\Widgets',
];

$filamentUiBaseline = [
    // arq-04, accepted debt: tenant and panel come from the Filament facade
    ResolveQuotaAllowance::class,
    HasTenant::class,
    // #319, goes away with Cashier
    BillingServiceProvider::class,
    // arq-12
    'TresPontosTech\Support\Filament',
    ChecksImportCompletion::class,
    'TresPontosTech\User\Filament',
];

foreach ($domain as $module) {
    arch($module . ' does not import integration modules')
        ->expect($module)
        ->not->toUse($integration);
}

foreach ([...$domain, ...$integration] as $module) {
    arch($module . ' does not import presentation modules')
        ->expect($module)
        ->not->toUse($presentation);

    arch($module . ' does not build Filament UI')
        ->expect($module)
        ->not->toUse($filamentUi)
        ->ignoring($filamentUiBaseline);
}
