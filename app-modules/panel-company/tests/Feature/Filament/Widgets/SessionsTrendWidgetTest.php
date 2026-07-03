<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetSessionsTrend;
use TresPontosTech\PanelCompany\DTOs\SessionsTrend;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\SessionsTrendWidget;

use function Pest\Livewire\livewire;

it('renders the sessions trend block for the tenant', function (): void {
    actingAsCompanyOwner();
    /** @var Company $company */
    $company = filament()->getTenant();
    Appointment::factory()->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);

    livewire(SessionsTrendWidget::class)
        ->assertOk()
        ->assertSee(__('panel-company::resources.pages.command_dashboard.trend.heading'));
});

it('renders without error when the trend series are empty', function (): void {
    actingAsCompanyOwner();

    // GetSessionsTrend is final, so swap it for a stub that yields empty series
    // to exercise the widget's max() guard against an empty data set.
    app()->instance(GetSessionsTrend::class, new class
    {
        public function handle(mixed ...$args): SessionsTrend
        {
            return new SessionsTrend(
                totalSeries: [],
                completedSeries: [],
                labels: [],
                completedTotal: 0,
                growthFactor: null,
            );
        }
    });

    livewire(SessionsTrendWidget::class)->assertOk();
});
