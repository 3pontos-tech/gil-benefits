<?php

declare(strict_types=1);

use App\Notifications\ContentNotification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentChangedNotification;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\EditAppointment;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    LaravelNotification::fake();
    Bus::fake();
    Mail::fake();

    $this->appointment = Appointment::factory()->create([
        'consultant_id' => Consultant::factory()->create()->getKey(),
        'appointment_at' => Date::now()->addDays(3)->setTime(15, 0),
        'status' => AppointmentStatus::Active,
        'meeting_url' => 'https://meet.google.com/aaa-bbbb-ccc',
    ]);
});

it('tells the employee, on the phone, that the room link changed', function (): void {
    livewire(EditAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->fillForm(['meeting_url' => 'https://meet.google.com/new-link-abc'])
        ->call('save')
        ->assertHasNoFormErrors();

    LaravelNotification::assertSentTo(
        $this->appointment->user,
        AppointmentChangedNotification::class,
        fn (AppointmentChangedNotification $notification): bool => $notification->content()->push?->data === ['change' => 'link']
            && in_array(ContentNotification::PUSH_CHANNEL, $notification->via($this->appointment->user), true),
    );
});

it('tells the employee the appointment is waiting for confirmation again when the consultant leaves', function (): void {
    livewire(EditAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->fillForm(['consultant_id' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->appointment->refresh()->status)->toBe(AppointmentStatus::Pending);
    LaravelNotification::assertSentTo(
        $this->appointment->user,
        AppointmentChangedNotification::class,
        fn (AppointmentChangedNotification $notification): bool => $notification->content()->push?->data === ['change' => 'unconfirmed'],
    );
});

it('says nothing when the save changed nothing the employee sees', function (): void {
    livewire(EditAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    LaravelNotification::assertNotSentTo($this->appointment->user, AppointmentChangedNotification::class);
});

it('says nothing about an appointment that was not confirmed yet', function (): void {
    $this->appointment->update(['status' => AppointmentStatus::Pending]);

    livewire(EditAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->fillForm(['meeting_url' => 'https://meet.google.com/new-link-abc'])
        ->call('save')
        ->assertHasNoFormErrors();

    LaravelNotification::assertNotSentTo($this->appointment->user, AppointmentChangedNotification::class);
});
