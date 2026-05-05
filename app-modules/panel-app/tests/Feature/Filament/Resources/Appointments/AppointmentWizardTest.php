<?php

declare(strict_types=1);

use TresPontosTech\App\Filament\Resources\Appointments\Pages\CreateAppointment;

use function Pest\Livewire\livewire;

it('renders the create appointment wizard for a subscribed employee', function (): void {
    actingAsSubscribedEmployee();

    livewire(CreateAppointment::class)
        ->assertOk();
});
