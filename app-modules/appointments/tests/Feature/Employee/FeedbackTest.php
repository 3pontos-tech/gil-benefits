<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\App\Filament\Resources\Appointments\Pages\ListAppointments;
use TresPontosTech\App\Filament\Widgets\AppointmentHistoryWidget;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $company = Company::factory()->create();
    $this->employee = User::factory()->employee()->create();
    $company->employees()->attach($this->employee);
    actingAs($this->employee);
    filament()->setTenant($company);
    filament()->setCurrentPanel(FilamentPanel::User->value);
});

it('shows feedback action on completed appointments without feedback', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->getKey(),
    ]);

    livewire(ListAppointments::class)
        ->assertTableActionVisible('feedback', $appointment);
});

it('hides feedback action on non-completed appointments', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create([
        'user_id' => $this->employee->getKey(),
    ]);

    livewire(ListAppointments::class)
        ->assertTableActionHidden('feedback', $appointment);
});

it('hides feedback action when appointment already has feedback', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->getKey(),
    ]);

    AppointmentFeedback::factory()->recycle($appointment)->recycle($this->employee)->create();

    livewire(ListAppointments::class)
        ->assertTableActionHidden('feedback', $appointment);
});

it('saves feedback with rating and comment', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->getKey(),
    ]);

    livewire(ListAppointments::class)
        ->callTableAction('feedback', $appointment, data: [
            'rating' => 5,
            'comment' => 'Excelente consultoria!',
        ])
        ->assertHasNoTableActionErrors();

    assertDatabaseHas(AppointmentFeedback::class, [
        'appointment_id' => $appointment->getKey(),
        'user_id' => $this->employee->getKey(),
        'rating' => 5,
        'comment' => 'Excelente consultoria!',
    ]);
});

it('saves feedback without comment as null', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->getKey(),
    ]);

    livewire(ListAppointments::class)
        ->callTableAction('feedback', $appointment, data: [
            'rating' => 3,
            'comment' => '',
        ])
        ->assertHasNoTableActionErrors();

    $feedback = AppointmentFeedback::query()->where('appointment_id', $appointment->getKey())->first();

    expect($feedback)->not->toBeNull()
        ->and($feedback->rating)->toBe(3)
        ->and($feedback->comment)->toBeNull();
});

it('does not allow duplicate feedback', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->getKey(),
    ]);

    AppointmentFeedback::factory()->recycle($appointment)->recycle($this->employee)->create();

    livewire(ListAppointments::class)
        ->assertTableActionHidden('feedback', $appointment);

    expect(AppointmentFeedback::query()->where('appointment_id', $appointment->getKey())->count())->toBe(1);
});

it('does not save feedback with invalid rating', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->getKey(),
    ]);

    livewire(ListAppointments::class)
        ->callTableAction('feedback', $appointment, data: [
            'rating' => 6,
            'comment' => null,
        ])
        ->assertHasTableActionErrors(['rating']);

    expect(AppointmentFeedback::query()->where('appointment_id', $appointment->getKey())->exists())->toBeFalse();
});

it('shows feedback action in appointment history widget', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->getKey(),
    ]);

    livewire(AppointmentHistoryWidget::class)
        ->assertTableActionVisible('feedback', $appointment);
});

it('hides feedback action in appointment history widget when already rated', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->getKey(),
    ]);

    AppointmentFeedback::factory()->recycle($appointment)->recycle($this->employee)->create();

    livewire(AppointmentHistoryWidget::class)
        ->assertTableActionHidden('feedback', $appointment);
});
