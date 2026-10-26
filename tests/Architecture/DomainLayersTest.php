<?php

declare(strict_types=1);

$identity = [
    'TresPontosTech\User',
    'TresPontosTech\Company',
    'TresPontosTech\Tenant',
    'TresPontosTech\Permissions',
];

$catalog = [
    'TresPontosTech\Consultants',
    'TresPontosTech\Billing',
];

$operation = [
    'TresPontosTech\Appointments',
    'TresPontosTech\Credits',
    'TresPontosTech\Vouchers',
    'TresPontosTech\Support',
];

$models = array_map(
    fn (string $module): string => $module . '\Models',
    [...$identity, ...$catalog, ...$operation],
);

$catalogBaseline = [
    // arq-08
    'TresPontosTech\Billing\Stripe',
];

foreach ($identity as $module) {
    arch($module . ' does not import catalog or operation modules')
        ->expect($module)
        ->not->toUse([...$catalog, ...$operation])
        ->ignoring($models);
}

foreach ($catalog as $module) {
    arch($module . ' does not import operation modules')
        ->expect($module)
        ->not->toUse($operation)
        ->ignoring([...$models, ...$catalogBaseline]);
}

arch('appointments does not import credits')
    ->expect('TresPontosTech\Appointments')
    ->not->toUse('TresPontosTech\Credits')
    ->ignoring($models);
