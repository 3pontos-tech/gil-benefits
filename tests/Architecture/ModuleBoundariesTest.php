<?php

declare(strict_types=1);

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

$filamentUiBaseline = [
    // arq-12
    'TresPontosTech\Support\Filament',
    ChecksImportCompletion::class,
    'TresPontosTech\User\Filament',
];

foreach ([...$domain, ...$integration] as $module) {
    arch($module . ' does not import presentation modules')
        ->expect($module)
        ->not->toUse($presentation);

    arch($module . ' does not build Filament UI')
        ->expect($module)
        ->not->toUse($filamentUi)
        ->ignoring($filamentUiBaseline);
}
