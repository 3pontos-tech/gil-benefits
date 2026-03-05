<?php

use TresPontosTech\Admin\Filament\Resources\Appointments\Pages\ListAppointments;
use TresPontosTech\Appointments\Models\Appointment;

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
