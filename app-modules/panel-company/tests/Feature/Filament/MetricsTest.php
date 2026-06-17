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

it('filters the metrics by a specific month', function (): void {
    actingAsCompanyOwner();

    livewire(Metrics::class)
        ->set('filters.month', now()->format('Y-m'))
        ->assertOk();
});
