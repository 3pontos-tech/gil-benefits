<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\CreateAppointment;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    LaravelNotification::fake();
    Bus::fake();
    Mail::fake();
});

it('creates a pending appointment without requiring a consultant', function (): void {
    $date = Date::now()->addDays(3)->setTime(10, 0);
    $user = User::factory()->create();

    livewire(CreateAppointment::class)
        ->fillForm([
            'user_id' => $user->id,
            'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
            'appointment_at' => $date->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $appointment = Appointment::query()->where('user_id', $user->id)->firstOrFail();

    // Respects the state machine: created as Pending, consultant assigned later on confirmation.
    expect($appointment->status)->toBe(AppointmentStatus::Pending)
        ->and($appointment->consultant_id)->toBeNull();

    // No agenda is blocked and no calendar event is created at creation time.
    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::APPOINTMENT)
        ->whereJsonContains('metadata->appointment_id', $appointment->id)
        ->exists()
    )->toBeFalse();

    Bus::assertNotDispatched(CreateAppointmentCalendarEventJob::class);
});

it('does not expose the consultant field on the create form', function (): void {
    livewire(CreateAppointment::class)
        ->assertFormFieldExists('user_id')
        ->assertFormFieldExists('category_type')
        ->assertFormFieldExists('appointment_at')
        ->assertFormFieldDoesNotExist('consultant_id');
});
