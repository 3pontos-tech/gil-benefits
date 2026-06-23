<?php

declare(strict_types=1);

use TresPontosTech\PanelApp\Enums\PlanStatus;

it('is a string-backed enum with the expected cases', function (): void {
    expect(PlanStatus::Active->value)->toBe('active')
        ->and(PlanStatus::Inactive->value)->toBe('inactive')
        ->and(PlanStatus::Expired->value)->toBe('expired');
});

it('resolves each label from the plan_status translations', function (PlanStatus $status, string $key): void {
    expect($status->getLabel())->toBe(__('panel-app::widgets.plan_status.' . $key));
})->with([
    'active' => [PlanStatus::Active, 'active'],
    'inactive' => [PlanStatus::Inactive, 'inactive'],
    'expired' => [PlanStatus::Expired, 'expired'],
]);
