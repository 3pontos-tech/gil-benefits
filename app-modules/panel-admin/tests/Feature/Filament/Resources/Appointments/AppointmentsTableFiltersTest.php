<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\ListAppointments;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('filters appointments by partial user name', function (): void {
    $john = User::factory()->create(['name' => 'John Doe']);
    $maria = User::factory()->create(['name' => 'Maria Silva']);

    $johnAppointment = Appointment::factory()->create(['user_id' => $john->id]);
    $mariaAppointment = Appointment::factory()->create(['user_id' => $maria->id]);

    livewire(ListAppointments::class)
        ->filterTable('user_name', ['user_name' => 'John'])
        ->assertCanSeeTableRecords([$johnAppointment])
        ->assertCanNotSeeTableRecords([$mariaAppointment]);
});

it('shows all appointments when the user name filter is empty', function (): void {
    $john = User::factory()->create(['name' => 'John Doe']);
    $maria = User::factory()->create(['name' => 'Maria Silva']);

    $johnAppointment = Appointment::factory()->create(['user_id' => $john->id]);
    $mariaAppointment = Appointment::factory()->create(['user_id' => $maria->id]);

    livewire(ListAppointments::class)
        ->filterTable('user_name', ['user_name' => ''])
        ->assertCanSeeTableRecords([$johnAppointment, $mariaAppointment]);
});

it('filters appointments by no-show status', function (): void {
    $noShow = Appointment::factory()->withStatus(AppointmentStatus::NoShow)->create();
    $active = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();

    livewire(ListAppointments::class)
        ->filterTable('status', [AppointmentStatus::NoShow->value])
        ->assertCanSeeTableRecords([$noShow])
        ->assertCanNotSeeTableRecords([$active]);
});
