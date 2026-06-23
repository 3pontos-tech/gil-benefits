<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Widgets\FinancialTopicsWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('marks covered topics and renders all categories', function (): void {
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->id,
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
    ]);

    livewire(FinancialTopicsWidget::class)
        ->assertSuccessful()
        ->assertSee('Temas financeiros')
        ->assertSee(AppointmentCategoryEnum::PersonalFinance->getLabel())
        ->assertSee(AppointmentCategoryEnum::RiskAndCompliance->getLabel());
});
