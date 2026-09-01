<?php

declare(strict_types=1);

use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\App;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;

it('translates each action type label according to the active locale', function (): void {
    App::setLocale('pt_BR');

    expect(AppointmentHistoryActionType::ConsultantAssigned->getLabel())->toBe('Consultor atribuído')
        ->and(AppointmentHistoryActionType::ConsultantLeft->getLabel())->toBe('Consultor removido')
        ->and(AppointmentHistoryActionType::ConsultantChanged->getLabel())->toBe('Consultor alterado')
        ->and(AppointmentHistoryActionType::ReScheduled->getLabel())->toBe('Reagendado')
        ->and(AppointmentHistoryActionType::NoShowMarked->getLabel())->toBe('Não comparecimento registrado');

    App::setLocale('en');

    expect(AppointmentHistoryActionType::ConsultantAssigned->getLabel())->toBe('Consultant assigned')
        ->and(AppointmentHistoryActionType::ReScheduled->getLabel())->toBe('Rescheduled')
        ->and(AppointmentHistoryActionType::NoShowMarked->getLabel())->toBe('No-show recorded');
});

it('never leaks the raw enum name as a label', function (): void {
    foreach (AppointmentHistoryActionType::cases() as $case) {
        expect($case->getLabel())->not->toBe($case->name);
    }
});

it('maps every action type to a distinct icon', function (): void {
    expect(AppointmentHistoryActionType::ConsultantAssigned->getIcon())->toBe(Heroicon::UserPlus)
        ->and(AppointmentHistoryActionType::ConsultantLeft->getIcon())->toBe(Heroicon::UserMinus)
        ->and(AppointmentHistoryActionType::ConsultantChanged->getIcon())->toBe(Heroicon::ArrowsRightLeft)
        ->and(AppointmentHistoryActionType::ReScheduled->getIcon())->toBe(Heroicon::Clock)
        ->and(AppointmentHistoryActionType::NoShowMarked->getIcon())->toBe(Heroicon::UserMinus);
});

it('maps every action type to a semantic color', function (): void {
    expect(AppointmentHistoryActionType::ConsultantAssigned->getColor())->toBe(Color::Green)
        ->and(AppointmentHistoryActionType::ConsultantLeft->getColor())->toBe(Color::Red)
        ->and(AppointmentHistoryActionType::ConsultantChanged->getColor())->toBe(Color::Blue)
        ->and(AppointmentHistoryActionType::ReScheduled->getColor())->toBe(Color::Amber)
        ->and(AppointmentHistoryActionType::NoShowMarked->getColor())->toBe(Color::Purple);
});
