<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Filament\Resources\Appointments\Pages\ViewAppointment;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->consultant = actingAsConsultant();
});

it('should render the view page for an appointment', function (): void {
    $appointment = $this->consultant->appointments->first();

    livewire(ViewAppointment::class, ['record' => $appointment->getRouteKey()])
        ->assertOk()
        ->assertSee($appointment->user->name);
});

it('shows financial profile section when client has anamnese', function (): void {
    $appointment = $this->consultant->appointments->first();

    UserAnamnese::factory()->for($appointment->user)->create([
        'main_motivation' => 'Quero sair das dívidas e construir uma reserva de emergência.',
    ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getRouteKey()])
        ->assertOk()
        ->assertSee('Quero sair das dívidas e construir uma reserva de emergência.');
});

it('shows warning section when client has no anamnese', function (): void {
    $appointment = Appointment::factory()
        ->recycle($this->consultant)
        ->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getRouteKey()])
        ->assertOk()
        ->assertSee(__('panel-consultant::resources.appointments.infolist.no_anamnese_description'));
});
