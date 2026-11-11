<?php

declare(strict_types=1);

use App\Notifications\ContentNotification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use TresPontosTech\Appointments\Actions\Transitions\PendingTransition;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentCancelledNotification;
use TresPontosTech\Appointments\Notifications\AppointmentConfirmedNotification;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Notification::fake();
    Mail::fake();
    Bus::fake();
    $this->travelTo('2026-09-08 09:00:00');
});

it('sends the confirmation to the phone with the day and time, and nothing about the meeting', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create(['appointment_at' => '2026-09-10 10:00:00']);
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData);

    Notification::assertSentTo($appointment->user, AppointmentConfirmedNotification::class, function (AppointmentConfirmedNotification $notification) use ($appointment): bool {
        $push = $notification->content()->push;

        return in_array(ContentNotification::PUSH_CHANNEL, $notification->via($appointment->user), true)
            && $push?->title === 'Encontro confirmado'
            && $push->body === 'Seu encontro de qui., 10/09, às 10:00, está confirmado. Toque para ver os detalhes.'
            && $push->urgent
            && $push->expiresAt?->equalTo($appointment->appointment_at);
    });
});

it('pushes a cancellation only when the team cancelled', function (CancellationActor $actor, bool $pushes): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create(['appointment_at' => '2026-09-12 15:00:00']);
    actingAs($appointment->user);

    (new PendingTransition($appointment))->handle(new TransitionData(cancellationActor: $actor));

    Notification::assertSentTo($appointment->user, AppointmentCancelledNotification::class, function (AppointmentCancelledNotification $notification) use ($appointment, $pushes): bool {
        $channels = $notification->via($appointment->user);

        return in_array('database', $channels, true)
            && in_array(ContentNotification::PUSH_CHANNEL, $channels, true) === $pushes
            && $notification->content()->push?->body === 'Seu encontro de sáb., 12/09, às 15:00, foi cancelado pela equipe. Toque para ver os detalhes e marcar outro.';
    });
})->with([
    'by the team' => [CancellationActor::Admin, true],
    'by the employee' => [CancellationActor::User, false],
    'by the system' => [CancellationActor::System, false],
]);
