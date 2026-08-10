<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\Pages\ListAppointmentFeedbacks;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('filters evaluations by rating', function (): void {
    $good = AppointmentFeedback::factory()->create(['rating' => 5, 'comment' => 'Excelente']);
    $bad = AppointmentFeedback::factory()->create(['rating' => 1, 'comment' => 'Ruim']);

    livewire(ListAppointmentFeedbacks::class)
        ->filterTable('rating', [5])
        ->assertCanSeeTableRecords([$good])
        ->assertCanNotSeeTableRecords([$bad]);
});

it('filters evaluations by company', function (): void {
    $company = Company::factory()->create();
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create(['company_id' => $company->id]);

    $inCompany = AppointmentFeedback::factory()->create([
        'appointment_id' => $appointment->id,
        'comment' => 'Da empresa',
    ]);
    $other = AppointmentFeedback::factory()->create(['comment' => 'De outra empresa']);

    livewire(ListAppointmentFeedbacks::class)
        ->filterTable('company_id', $company->id)
        ->assertCanSeeTableRecords([$inCompany])
        ->assertCanNotSeeTableRecords([$other]);
});

it('filters evaluations by partial consultant name', function (): void {
    $consultant = Consultant::factory()->create(['name' => 'Carlos Mendes']);
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create(['consultant_id' => $consultant->id]);

    $fromCarlos = AppointmentFeedback::factory()->create([
        'appointment_id' => $appointment->id,
        'comment' => 'Do Carlos',
    ]);
    $other = AppointmentFeedback::factory()->create(['comment' => 'De outro consultor']);

    livewire(ListAppointmentFeedbacks::class)
        ->filterTable('consultant_name', ['consultant_name' => 'Carlos'])
        ->assertCanSeeTableRecords([$fromCarlos])
        ->assertCanNotSeeTableRecords([$other]);
});

it('filters evaluations by partial beneficiary name', function (): void {
    $john = User::factory()->create(['name' => 'John Doe']);

    $fromJohn = AppointmentFeedback::factory()->create([
        'user_id' => $john->id,
        'comment' => 'Do John',
    ]);
    $other = AppointmentFeedback::factory()->create(['comment' => 'De outra pessoa']);

    livewire(ListAppointmentFeedbacks::class)
        ->filterTable('user_name', ['user_name' => 'John'])
        ->assertCanSeeTableRecords([$fromJohn])
        ->assertCanNotSeeTableRecords([$other]);
});

it('filters evaluations by evaluation period', function (): void {
    $old = AppointmentFeedback::factory()->create([
        'comment' => 'Antiga',
        'created_at' => now()->subMonth(),
    ]);
    $recent = AppointmentFeedback::factory()->create([
        'comment' => 'Recente',
        'created_at' => now(),
    ]);

    livewire(ListAppointmentFeedbacks::class)
        ->filterTable('date_range', ['from' => now()->subWeek()->toDateString()])
        ->assertCanSeeTableRecords([$recent])
        ->assertCanNotSeeTableRecords([$old]);
});

it('filters evaluations with and without comment', function (): void {
    $withComment = AppointmentFeedback::factory()->create(['comment' => 'Muito bom']);
    $withoutComment = AppointmentFeedback::factory()->create(['comment' => null]);

    livewire(ListAppointmentFeedbacks::class)
        ->filterTable('has_comment', true)
        ->assertCanSeeTableRecords([$withComment])
        ->assertCanNotSeeTableRecords([$withoutComment])
        ->filterTable('has_comment', false)
        ->assertCanSeeTableRecords([$withoutComment])
        ->assertCanNotSeeTableRecords([$withComment]);
});

it('filters evaluations by appointment status', function (): void {
    $completed = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create();
    $cancelled = Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->create();

    $fromCompleted = AppointmentFeedback::factory()->create([
        'appointment_id' => $completed->id,
        'comment' => 'Concluída',
    ]);
    $fromCancelled = AppointmentFeedback::factory()->create([
        'appointment_id' => $cancelled->id,
        'comment' => 'Cancelada',
    ]);

    livewire(ListAppointmentFeedbacks::class)
        ->filterTable('appointment_status', [AppointmentStatus::Completed])
        ->assertCanSeeTableRecords([$fromCompleted])
        ->assertCanNotSeeTableRecords([$fromCancelled]);
});

it('combines multiple filters', function (): void {
    $match = AppointmentFeedback::factory()->create(['rating' => 5, 'comment' => 'Excelente']);
    $wrongRating = AppointmentFeedback::factory()->create(['rating' => 3, 'comment' => 'Mediano']);
    $noComment = AppointmentFeedback::factory()->create(['rating' => 5, 'comment' => null]);

    livewire(ListAppointmentFeedbacks::class)
        ->filterTable('rating', [5])
        ->filterTable('has_comment', true)
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$wrongRating, $noComment]);
});
