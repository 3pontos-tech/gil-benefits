<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\Notifications\NotificationKind;
use Filament\Notifications\Livewire\DatabaseNotifications;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentCancelledNotification;

use function Pest\Livewire\livewire;

it('stores the kind as the type and keeps the Filament format with the ids next to it', function (): void {
    $user = User::factory()->create();
    $appointment = Appointment::factory()->create(['user_id' => $user->id]);

    $user->notify(new AppointmentCancelledNotification($appointment, isLate: false));

    $stored = $user->notifications()->sole();

    expect($stored->type)->toBe(NotificationKind::AppointmentCancelled->value)
        ->and($stored->data)->toMatchArray([
            'format' => 'filament',
            'status' => 'warning',
            'title' => __('appointments::resources.appointments.notifications.cancelled.title'),
            'kind' => 'appointment_cancelled',
            'appointment_id' => $appointment->id,
        ])
        ->and($stored->data)->not->toHaveKey('document_id');
});

it('still shows in the panel bell', function (): void {
    $user = actingAsEmployee();
    $appointment = Appointment::factory()->create(['user_id' => $user->id]);

    $user->notify(new AppointmentCancelledNotification($appointment, isLate: true));

    livewire(DatabaseNotifications::class)
        ->assertSee(__('appointments::resources.appointments.notifications.user_cancelled_late.title'));
});
