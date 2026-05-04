<?php

use TresPontosTech\Admin\Filament\Resources\Appointments\Pages\ListAppointments;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(ListAppointments::class)
        ->assertOk();
});

it('should list appointments', function (): void {
    $appointments = Appointment::factory()->count(8)->create();
    livewire(ListAppointments::class)
        ->assertOk()
        ->assertCanSeeTableRecords($appointments);
});

it('filters appointments by company', function (): void {
    $company = Company::factory()->create();
    $companyAppointments = Appointment::factory()->count(3)->create(['company_id' => $company->id]);
    $otherAppointments = Appointment::factory()->count(2)->create();

    livewire(ListAppointments::class)
        ->filterTable('company_id', $company->id)
        ->assertCanSeeTableRecords($companyAppointments)
        ->assertCanNotSeeTableRecords($otherAppointments);
});
