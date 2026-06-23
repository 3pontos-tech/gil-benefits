<?php

use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\ViewAppointment;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    $this->appointment = Appointment::factory()->withoutConsultant()->create();
});

it('should render', function (): void {
    livewire(ViewAppointment::class, ['record' => $this->appointment->getKey()])
        ->assertOk();
});
