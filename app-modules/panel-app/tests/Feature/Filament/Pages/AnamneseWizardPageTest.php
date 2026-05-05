<?php

declare(strict_types=1);

use TresPontosTech\App\Filament\Pages\AnamneseWizardPage;
use TresPontosTech\User\Enums\LifeMoment;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsEmployee();
});

it('renders the anamnese wizard page', function (): void {
    livewire(AnamneseWizardPage::class)
        ->assertOk();
});

it('saves the anamnese on submit', function (): void {
    livewire(AnamneseWizardPage::class)
        ->set('data.life_moment', LifeMoment::Saver->value)
        ->set('data.main_motivation', 'I want financial freedom.')
        ->set('data.money_relationship', 'I manage money carefully.')
        ->set('data.plans_monthly_expenses', '3000')
        ->set('data.tried_financial_strategies', 'Budgeting and investing.')
        ->call('submit')
        ->assertNotified();

    expect(UserAnamnese::query()->where('user_id', $this->employee->id)->exists())->toBeTrue();
});
