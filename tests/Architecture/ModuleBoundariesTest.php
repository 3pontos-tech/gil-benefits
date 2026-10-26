<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Actions\Transitions\AbstractAppointmentTransition;
use TresPontosTech\Appointments\Mail\AppointmentRecordPublishedMail;
use TresPontosTech\Billing\Core\Filament\CompanyBillingProvider;
use TresPontosTech\Billing\Core\Filament\UserBillingProvider;
use TresPontosTech\Billing\Core\Http\Middleware\RedirectCompanyIfNotSubscribed;
use TresPontosTech\Billing\Core\Http\Middleware\RedirectUserIfNotSubscribed;
use TresPontosTech\Billing\Core\Pages\BillingManagePage;
use TresPontosTech\Billing\Core\Pages\TenantSubscriptionPage;
use TresPontosTech\IntegrationBarte\Actions\HandleBarteWebhook;
use TresPontosTech\IntegrationBarte\BarteAdapter;
use TresPontosTech\IntegrationVirtu\VirtuAdapter;
use TresPontosTech\User\Concerns\ChecksImportCompletion;
use TresPontosTech\Vouchers\Actions\RequestVoucherBatchPdf;
use TresPontosTech\Vouchers\Jobs\GenerateVoucherBatchPdfJob;

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
    // arq-01 step 2
    BarteAdapter::class,
    VirtuAdapter::class,
    // arq-01 step 3
    AppointmentRecordPublishedMail::class,
    // arq-01 step 5
    BillingManagePage::class,
    UserBillingProvider::class,
];

$filamentUiBaseline = [
    // arq-01 step 4
    AbstractAppointmentTransition::class,
    HandleBarteWebhook::class,
    RequestVoucherBatchPdf::class,
    GenerateVoucherBatchPdfJob::class,
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
