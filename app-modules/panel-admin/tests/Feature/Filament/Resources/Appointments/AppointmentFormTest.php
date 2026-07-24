<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\EditAppointment;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('clears consultant_id when appointment_at is changed', function (): void {
    // The consultant field only exists on the edit form (assigned via confirmation on create).
    $appointment = Appointment::factory()->create();

    livewire(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->assertSet('data.consultant_id', $appointment->consultant_id)
        ->set('data.appointment_at', now()->addDays(3)->toDateTimeString())
        ->assertSet('data.consultant_id', null);
});
