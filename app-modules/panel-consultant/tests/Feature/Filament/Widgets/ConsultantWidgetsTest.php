<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Filament\Widgets\ConsultantAppointmentHistoryWidget;
use TresPontosTech\Consultants\Filament\Widgets\ConsultantLatestAppointmentWidget;
use TresPontosTech\Consultants\Filament\Widgets\ConsultantStatsOverview;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->consultant = actingAsConsultant();
});

describe('ConsultantStatsOverview', function (): void {
    it('renders with the count of completed appointments from the last 30 days', function (): void {
        $this->consultant->appointments()->delete();

        Appointment::factory()
            ->recycle($this->consultant)
            ->withStatus(AppointmentStatus::Completed)
            ->count(3)
            ->create(['appointment_at' => now()->subDays(5)]);

        livewire(ConsultantStatsOverview::class)
            ->assertOk()
            ->assertSee(__('panel-consultant::widgets.stats_overview.label'))
            ->assertSee('3');
    });

    it('does not count appointments from other consultants', function (): void {
        $this->consultant->appointments()->delete();

        Appointment::factory()
            ->withStatus(AppointmentStatus::Completed)
            ->count(5)
            ->create(['appointment_at' => now()->subDays(2)]);

        livewire(ConsultantStatsOverview::class)
            ->assertOk()
            ->assertSee('0');
    });
});

describe('ConsultantLatestAppointmentWidget', function (): void {
    it('renders with the most recent appointment for the consultant', function (): void {
        $latest = Appointment::factory()
            ->recycle($this->consultant)
            ->withStatus(AppointmentStatus::Active)
            ->create(['appointment_at' => now()->addDays(2)]);

        livewire(ConsultantLatestAppointmentWidget::class)
            ->assertOk()
            ->assertSee($latest->user->name);
    });

    it('still renders when the consultant has no appointments', function (): void {
        $this->consultant->appointments()->delete();

        livewire(ConsultantLatestAppointmentWidget::class)
            ->assertOk();
    });
});

describe('ConsultantAppointmentHistoryWidget', function (): void {
    it('lists only appointments of the authenticated consultant', function (): void {
        $otherAppointments = Appointment::factory()->count(3)->create();

        livewire(ConsultantAppointmentHistoryWidget::class)
            ->assertOk()
            ->assertCanNotSeeTableRecords($otherAppointments);
    });

    it('caps the history at the 5 latest appointments', function (): void {
        $this->consultant->appointments()->delete();

        $appointments = collect(range(1, 8))->map(fn (int $i) => Appointment::factory()
            ->recycle($this->consultant)
            ->create(['appointment_at' => now()->subDays($i)])
        );

        $latestFive = $appointments->take(5);
        $older = $appointments->slice(5);

        livewire(ConsultantAppointmentHistoryWidget::class)
            ->assertOk()
            ->assertCanSeeTableRecords($latestFive)
            ->assertCanNotSeeTableRecords($older);
    });
});
