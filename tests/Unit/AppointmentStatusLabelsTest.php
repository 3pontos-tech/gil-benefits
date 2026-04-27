<?php

use Illuminate\Support\Facades\App;
use Tests\TestCase;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

uses(TestCase::class);

it('returns English labels for appointment statuses', function (): void {
    App::setLocale('en');

    expect(AppointmentStatus::Pending->getLabel())->toBe('Pending');
    expect(AppointmentStatus::Active->getLabel())->toBe('Scheduled');
    expect(AppointmentStatus::Completed->getLabel())->toBe('Completed');
    expect(AppointmentStatus::Cancelled->getLabel())->toBe('Cancelled');
    expect(AppointmentStatus::CancelledLate->getLabel())->toBe('Cancelled (Late)');
});

it('returns Brazilian Portuguese labels for appointment statuses', function (): void {
    App::setLocale('pt_BR');

    expect(AppointmentStatus::Pending->getLabel())->toBe('Pendente');
    expect(AppointmentStatus::Active->getLabel())->toBe('Agendado');
    expect(AppointmentStatus::Completed->getLabel())->toBe('Concluído');
    expect(AppointmentStatus::Cancelled->getLabel())->toBe('Cancelado');
    expect(AppointmentStatus::CancelledLate->getLabel())->toBe('Cancelado (fora do prazo)');
});
