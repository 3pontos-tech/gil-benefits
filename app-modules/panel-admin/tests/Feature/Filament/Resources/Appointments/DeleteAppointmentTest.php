<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\EditAppointment;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\ListAppointments;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('offers no delete action on the edit page', function (): void {
    $appointment = Appointment::factory()->create();

    livewire(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->assertOk()
        ->assertActionDoesNotExist('delete');
});

it('offers no bulk delete on the list', function (): void {
    Appointment::factory()->create();

    livewire(ListAppointments::class)
        ->assertOk()
        ->assertTableBulkActionDoesNotExist('delete');
});
