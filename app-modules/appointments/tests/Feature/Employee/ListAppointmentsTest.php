<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\App\Filament\Resources\Appointments\Pages\ListAppointments;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $company = Company::factory()->create();
    $this->employee = User::factory()->employee()->create();
    $company->employees()->attach($this->employee);
    actingAs($this->employee);
    filament()->setTenant($company);
    filament()->setCurrentPanel(FilamentPanel::User->value);

});

it('should render', function (): void {
    livewire(ListAppointments::class)
        ->assertOk();
});

it('should list only the employee appointments', function (): void {
    $employeeAppointments = Appointment::factory()->state(['user_id' => $this->employee->getKey()])->count(5)->create();
    $anotherAppointments = Appointment::factory()->count(5)->create();

    livewire(ListAppointments::class)
        ->assertOk()
        ->assertCanSeeTableRecords($employeeAppointments)
        ->assertCanNotSeeTableRecords($anotherAppointments);
});
