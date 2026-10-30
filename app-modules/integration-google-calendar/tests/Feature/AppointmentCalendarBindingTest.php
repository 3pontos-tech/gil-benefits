<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Contracts\AppointmentCalendar;
use TresPontosTech\IntegrationGoogleCalendar\AppointmentCalendarSynchronizer;

it('answers the appointments module calendar contract with the Google synchronizer', function (): void {
    expect(resolve(AppointmentCalendar::class))->toBeInstanceOf(AppointmentCalendarSynchronizer::class);
});
