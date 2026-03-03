<?php

use TresPontosTech\Appointments\Filament\App\Resources\Appointments\Pages\EditAppointment;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    $this->appointment = Appointment::factory()->withoutConsultant()->draft()->state()->create();
});

it('should render', function (): void {
    livewire(EditAppointment::class, ['record' => $this->appointment->getKey()])
        ->assertOk();
});
