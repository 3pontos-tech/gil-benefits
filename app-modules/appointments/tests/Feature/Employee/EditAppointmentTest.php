<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\Pages\EditAppointment;
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

    $this->appointment = Appointment::factory()->for($this->employee, 'user')->withoutConsultant()->draft()->create();
});

it('should render', function (): void {
    livewire(EditAppointment::class, ['record' => $this->appointment->getKey()])
        ->assertOk();
});
