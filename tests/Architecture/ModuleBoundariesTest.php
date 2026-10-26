<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Filament\CompanyBillingProvider;
use TresPontosTech\Billing\Core\Filament\UserBillingProvider;
use TresPontosTech\Billing\Core\Http\Middleware\RedirectCompanyIfNotSubscribed;
use TresPontosTech\Billing\Core\Http\Middleware\RedirectUserIfNotSubscribed;
use TresPontosTech\Billing\Core\Pages\BillingManagePage;
use TresPontosTech\Billing\Core\Pages\TenantSubscriptionPage;
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
    'Filament\Forms',
    'Filament\Infolists',
    'Filament\Notifications',
    'Filament\Pages',
    'Filament\Resources',
    'Filament\Schemas',
    'Filament\Tables',
    'Filament\Widgets',
];

$presentationBaseline = [
    // arq-01 step 5
    BillingManagePage::class,
    UserBillingProvider::class,
];

$filamentUiBaseline = [
    // arq-01 step 5
    CompanyBillingProvider::class,
    UserBillingProvider::class,
    BillingManagePage::class,
    TenantSubscriptionPage::class,
    RedirectCompanyIfNotSubscribed::class,
    RedirectUserIfNotSubscribed::class,
    // arq-12
    'TresPontosTech\Support\Filament',
    ChecksImportCompletion::class,
    'TresPontosTech\User\Filament',
];

foreach ([...$domain, ...$integration] as $module) {
    arch($module . ' does not import presentation modules')
        ->expect($module)
        ->not->toUse($presentation)
        ->ignoring($presentationBaseline);

    arch($module . ' does not build Filament UI')
        ->expect($module)
        ->not->toUse($filamentUi)
        ->ignoring($filamentUiBaseline);
}
