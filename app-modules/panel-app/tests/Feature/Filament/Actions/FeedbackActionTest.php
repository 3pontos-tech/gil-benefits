<?php

declare(strict_types=1);

use TresPontosTech\App\Filament\Resources\Appointments\Pages\ListAppointments;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('is visible on a completed appointment with no feedback', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create(['user_id' => $this->employee->id]);

    livewire(ListAppointments::class)
        ->assertTableActionVisible('feedback', $appointment);
});

it('is hidden on a completed appointment that already has feedback', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create(['user_id' => $this->employee->id]);

    AppointmentFeedback::factory()->create([
        'appointment_id' => $appointment->id,
        'user_id' => $this->employee->id,
    ]);

    livewire(ListAppointments::class)
        ->assertTableActionHidden('feedback', $appointment);
});

it('is hidden on non-completed appointments', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()
        ->withStatus($status)
        ->create(['user_id' => $this->employee->id]);

    livewire(ListAppointments::class)
        ->assertTableActionHidden('feedback', $appointment);
})->with([
    'Pending' => AppointmentStatus::Pending,
    'Active' => AppointmentStatus::Active,
    'Cancelled' => AppointmentStatus::Cancelled,
]);

it('creates an AppointmentFeedback record when submitted', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create(['user_id' => $this->employee->id]);

    livewire(ListAppointments::class)
        ->callTableAction('feedback', $appointment, data: [
            'rating' => 5,
            'comment' => 'Excellent session!',
        ])
        ->assertNotified();

    expect(AppointmentFeedback::query()->where('appointment_id', $appointment->id)->exists())->toBeTrue();
});
