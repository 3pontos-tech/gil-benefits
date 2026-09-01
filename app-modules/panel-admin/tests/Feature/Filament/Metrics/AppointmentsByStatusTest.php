<?php

declare(strict_types=1);

use Filament\Support\Colors\Color;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\AppointmentsByStatus;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

function appointmentsByStatusPageFilters(): array
{
    return ['pageFilters' => ['startDate' => null, 'endDate' => null]];
}

it('renders the appointments by status widget', function (): void {
    livewire(AppointmentsByStatus::class, appointmentsByStatusPageFilters())->assertOk();
});

it('derives each case color and label from the enum itself, never from its position in the dataset', function (): void {
    Appointment::factory()->withStatus(AppointmentStatus::NoShow)->count(2)->create();
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->count(3)->create();

    $widget = livewire(AppointmentsByStatus::class, appointmentsByStatusPageFilters())->instance();

    $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

    foreach (AppointmentStatus::cases() as $status) {
        $index = array_search($status->getLabel(), $data['labels'], true);

        expect($index)->not->toBeFalse();
        expect($data['datasets'][0]['backgroundColor'][$index])
            ->toBe(Color::convertToRgb($status->getColor()[500]));
    }
});
