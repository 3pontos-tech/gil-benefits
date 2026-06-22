<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\PanelCompany\Filament\Pages\Metrics;

use function Pest\Livewire\livewire;

beforeEach(fn () => Cache::flush());

it('renders the metrics page for a company owner', function (): void {
    actingAsCompanyOwner();

    livewire(Metrics::class)->assertOk();
});

it('filters the metrics by a date range', function (): void {
    actingAsCompanyOwner();

    livewire(Metrics::class)
        ->set('filters.startDate', now()->subDays(15)->toDateString())
        ->set('filters.endDate', now()->toDateString())
        ->assertOk();
});

it('falls back to the default range with no date filters', function (): void {
    actingAsCompanyOwner();

    livewire(Metrics::class)
        ->set('filters.startDate')
        ->set('filters.endDate')
        ->assertOk();
});

it('shows the five metrics tabs', function (): void {
    actingAsCompanyOwner();

    livewire(Metrics::class)
        ->assertOk()
        ->assertSee(__('panel-company::resources.pages.metrics.tab_sessions'))
        ->assertSee(__('panel-company::resources.pages.metrics.tab_adoption'))
        ->assertSee(__('panel-company::resources.pages.metrics.tab_experience'))
        ->assertSee(__('panel-company::resources.pages.metrics.tab_credits'));
});

it('groups widgets into panorama and period scope sections', function (): void {
    actingAsCompanyOwner();

    livewire(Metrics::class)
        ->assertOk()
        ->assertSee(__('panel-company::resources.pages.metrics.scope_panorama_heading'))
        ->assertSee(__('panel-company::resources.pages.metrics.scope_period_heading'));
});
