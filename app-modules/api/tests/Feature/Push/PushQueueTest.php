<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;
use TresPontosTech\Api\DTOs\Push\ExpoPushMessage;
use TresPontosTech\Api\Jobs\SendPushNotificationJob;
use TresPontosTech\Appointments\Jobs\SendAppointmentRemindersJob;
use TresPontosTech\Credits\Jobs\WarnExpiringCreditsJob;

/**
 * O push tem fila própria, para não esperar atrás de atas geradas por IA, sincronização de agenda ou levas de e-mail.
 * O worker de cada ambiente precisa consumi-la (app-modules/api/README.md, "Deploy por ambiente").
 */
it('sends pushes on their own queue', function (): void {
    $job = new SendPushNotificationJob('user-1', new ExpoPushMessage('Encontro confirmado', 'Corpo', ['type' => 'appointment_confirmed'], urgent: true, androidChannel: 'appointments'));

    expect($job->queue)->toBe('push');
});

it('puts the scheduled jobs that create pushes on the same queue', function (string $jobClass): void {
    Queue::fake();
    $event = collect(resolve(Schedule::class)->events())->first(fn (Event $event): bool => $event->description === $jobClass);

    $event?->run(app());

    Queue::assertPushedOn('push', $jobClass);
})->with([
    'the reminder' => SendAppointmentRemindersJob::class,
    'the credits warning' => WarnExpiringCreditsJob::class,
]);
