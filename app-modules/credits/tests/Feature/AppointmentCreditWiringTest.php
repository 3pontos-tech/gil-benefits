<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Events\AppointmentCompleted;
use TresPontosTech\Appointments\Events\AppointmentNoShow;
use TresPontosTech\Appointments\Events\AppointmentReserved;
use TresPontosTech\Credits\Listeners\ConsumeCreditOnAppointmentReserved;
use TresPontosTech\Credits\Listeners\MarkCreditUsedOnAppointmentCompleted;
use TresPontosTech\Credits\Listeners\MarkCreditUsedOnAppointmentNoShow;
use TresPontosTech\Credits\Listeners\SettleCreditOnAppointmentCancelled;

it('wires the credit listener to each appointment event, exactly once', function (string $event, string $listener): void {
    $registered = array_filter(
        Event::getRawListeners()[$event] ?? [],
        fn (mixed $raw): bool => $raw === $listener . '@handle' || $raw === $listener,
    );

    expect($registered)->toHaveCount(1);
})->with([
    'reserved' => [AppointmentReserved::class, ConsumeCreditOnAppointmentReserved::class],
    'cancelled' => [AppointmentCancelled::class, SettleCreditOnAppointmentCancelled::class],
    'completed' => [AppointmentCompleted::class, MarkCreditUsedOnAppointmentCompleted::class],
    'no-show' => [AppointmentNoShow::class, MarkCreditUsedOnAppointmentNoShow::class],
]);
