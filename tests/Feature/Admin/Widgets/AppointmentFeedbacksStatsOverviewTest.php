<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\PanelAdmin\Filament\Widgets\AppointmentFeedbacksStatsOverview;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('renders successfully', function (): void {
    livewire(AppointmentFeedbacksStatsOverview::class)
        ->assertOk();
});

it('shows the total count of evaluations', function (): void {
    AppointmentFeedback::factory()->count(3)->create(['comment' => 'Ok']);

    livewire(AppointmentFeedbacksStatsOverview::class)
        ->assertSeeInOrder([__('panel-admin::widgets.appointment_feedbacks_stats.total'), '3']);
});

it('calculates the average rating', function (): void {
    AppointmentFeedback::factory()->create(['rating' => 5, 'comment' => 'Ok']);
    AppointmentFeedback::factory()->create(['rating' => 4, 'comment' => 'Ok']);

    livewire(AppointmentFeedbacksStatsOverview::class)
        ->assertSeeInOrder([__('panel-admin::widgets.appointment_feedbacks_stats.avg_rating'), '4.5/5']);
});

it('calculates the share of evaluations with a comment', function (): void {
    AppointmentFeedback::factory()->create(['rating' => 5, 'comment' => 'Muito bom']);
    AppointmentFeedback::factory()->create(['rating' => 4, 'comment' => null]);

    livewire(AppointmentFeedbacksStatsOverview::class)
        ->assertSeeInOrder([__('panel-admin::widgets.appointment_feedbacks_stats.with_comment_rate'), '50%']);
});

it('counts critical evaluations with rating up to two', function (): void {
    AppointmentFeedback::factory()->create(['rating' => 1, 'comment' => 'Ruim']);
    AppointmentFeedback::factory()->create(['rating' => 2, 'comment' => 'Fraco']);
    AppointmentFeedback::factory()->create(['rating' => 5, 'comment' => 'Excelente']);

    livewire(AppointmentFeedbacksStatsOverview::class)
        ->assertSeeInOrder([__('panel-admin::widgets.appointment_feedbacks_stats.critical'), '2']);
});

it('ignores evaluations of soft-deleted appointments', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create();
    AppointmentFeedback::factory()->create([
        'appointment_id' => $appointment->id,
        'rating' => 1,
        'comment' => 'Apagada',
    ]);
    AppointmentFeedback::factory()->create(['rating' => 5, 'comment' => 'Ativa']);

    $appointment->delete();

    livewire(AppointmentFeedbacksStatsOverview::class)
        ->assertSeeInOrder([__('panel-admin::widgets.appointment_feedbacks_stats.total'), '1']);
});

it('computes the stats respecting the table filter state', function (): void {
    AppointmentFeedback::factory()->create(['rating' => 5, 'comment' => 'Excelente']);
    AppointmentFeedback::factory()->create(['rating' => 1, 'comment' => 'Ruim']);

    livewire(AppointmentFeedbacksStatsOverview::class, [
        'tableFilterState' => ['rating' => ['values' => [5]]],
    ])
        ->assertSeeInOrder([__('panel-admin::widgets.appointment_feedbacks_stats.total'), '1']);
});

it('syncs the filter state dispatched by the evaluations table', function (): void {
    livewire(AppointmentFeedbacksStatsOverview::class)
        ->dispatch('appointment-feedbacks-table-filters-changed', filters: ['rating' => ['values' => [5]]])
        ->assertSet('tableFilterState', ['rating' => ['values' => [5]]]);
});
