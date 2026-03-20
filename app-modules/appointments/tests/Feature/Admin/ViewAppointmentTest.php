<?php

use TresPontosTech\Admin\Filament\Resources\Appointments\Pages\ViewAppointment;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    $this->appointment = Appointment::factory()->withoutConsultant()->draft()->create();
});

it('should render', function (): void {
    livewire(ViewAppointment::class, ['record' => $this->appointment->getKey()])
        ->assertOk();
});
