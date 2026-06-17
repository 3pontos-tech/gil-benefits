<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\AdoptionFunnelWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\CategoryMixWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\CreditKpisWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\DepartmentAdoptionWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\SatisfactionWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\StatusBreakdownWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\TopConsultantsWidget;

use function Pest\Livewire\livewire;

beforeEach(fn () => Cache::flush());

it('renders each command dashboard widget for a company owner', function (string $widget, string $key): void {
    actingAsCompanyOwner();

    livewire($widget)
        ->assertOk()
        ->assertSee(__('panel-company::resources.pages.command_dashboard.' . $key));
})->with([
    'adoption funnel' => [AdoptionFunnelWidget::class, 'funnel.heading'],
    'credit kpis' => [CreditKpisWidget::class, 'credits.total'],
    'category mix' => [CategoryMixWidget::class, 'categories.heading'],
    'status breakdown' => [StatusBreakdownWidget::class, 'status.heading'],
    'department adoption' => [DepartmentAdoptionWidget::class, 'departments.heading'],
    'satisfaction' => [SatisfactionWidget::class, 'satisfaction.heading'],
    'top consultants' => [TopConsultantsWidget::class, 'consultants.heading'],
]);
