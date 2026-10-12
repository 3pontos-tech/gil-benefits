<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\Schemas\AppointmentWizard;

beforeEach(function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    actingAsEmployee();
});

it('lists slots up to the last day of the 60-day window and none after it', function (): void {
    consultantAvailableOn(Date::parse('2026-12-06'), Date::parse('2026-12-07'));

    expect(AppointmentWizard::availableSlots('2026-12-06'))->not->toBeEmpty()
        ->and(AppointmentWizard::availableSlots('2026-12-07'))->toBe([]);
});

it('disables the calendar days after the window', function (): void {
    $html = view('filament.app.appointments.wizard.calendar-field', [
        'getStatePath' => fn (): string => 'data.date',
        'getState' => fn (): null => null,
        'minDate' => '2026-10-09',
        'maxDate' => '2026-12-06',
    ])->render();

    expect($html)->toContain("max: '2026-12-06'")
        ->and($html)->toContain('iso > this.max');
});

it('keeps the calendar open-ended where no maximum is given', function (): void {
    $html = view('filament.app.appointments.wizard.calendar-field', [
        'getStatePath' => fn (): string => 'data.date',
        'getState' => fn (): null => null,
        'minDate' => '2026-10-09',
    ])->render();

    expect($html)->toContain('max: null');
});
