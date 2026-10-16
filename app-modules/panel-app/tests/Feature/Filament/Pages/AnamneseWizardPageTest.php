<?php

declare(strict_types=1);

use TresPontosTech\PanelApp\Filament\Pages\AnamneseWizardPage;
use TresPontosTech\User\Enums\LifeMoment;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Livewire\livewire;

it('opens with the answers already given in the app', function (): void {
    $employee = actingAsSubscribedEmployee();
    UserAnamnese::factory()->for($employee)->create([
        'life_moment' => LifeMoment::Payer,
        'main_motivation' => 'Organizar as contas',
        'money_relationship' => null,
        'plans_monthly_expenses' => null,
        'tried_financial_strategies' => null,
    ]);

    livewire(AnamneseWizardPage::class)
        ->assertSchemaStateSet([
            'life_moment' => 'payer',
            'main_motivation' => 'Organizar as contas',
            'money_relationship' => null,
        ]);
});

it('still requires the five answers to finish', function (): void {
    $employee = actingAsSubscribedEmployee();
    UserAnamnese::factory()->for($employee)->create([
        'life_moment' => LifeMoment::Payer,
        'main_motivation' => 'Organizar as contas',
        'money_relationship' => null,
        'plans_monthly_expenses' => null,
        'tried_financial_strategies' => null,
    ]);

    livewire(AnamneseWizardPage::class)
        ->call('submit')
        ->assertHasFormErrors(['money_relationship', 'plans_monthly_expenses', 'tried_financial_strategies']);

    expect($employee->anamnese()->first()->isComplete())->toBeFalse();
});
