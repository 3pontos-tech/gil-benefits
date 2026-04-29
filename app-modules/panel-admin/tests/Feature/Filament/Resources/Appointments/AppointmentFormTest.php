<?php

declare(strict_types=1);

use TresPontosTech\Admin\Filament\Resources\Appointments\Pages\CreateAppointment;
use TresPontosTech\Consultants\Models\Consultant;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('clears consultant_id when appointment_at is changed', function (): void {
    $consultant = Consultant::factory()->create();

    livewire(CreateAppointment::class)
        ->set('data.consultant_id', $consultant->id)
        ->assertSet('data.consultant_id', $consultant->id)
        ->set('data.appointment_at', now()->addDays(3)->toDateTimeString())
        ->assertSet('data.consultant_id', null);
});
