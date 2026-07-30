<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use TresPontosTech\Company\Models\Company;

/**
 * Shown to an authenticated member of a company whose plan is inactive (never
 * paid or cancelled), instead of a bare 403. The member cannot fix the billing
 * themselves, so the page explains the situation and points them to their
 * company admin/manager. It is reachable through
 * `RedirectUserIfNotSubscribed`, which exempts this route to avoid a loop.
 */
class CompanyPlanInactive extends Page
{
    protected static ?string $slug = 'company-plan-inactive';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected Width|string|null $maxContentWidth = Width::Medium;

    protected string $view = 'company-plan-inactive';

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string
    {
        return __('views.company_plan_inactive.title');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $tenant = filament()->getTenant();

        return [
            'companyName' => $tenant instanceof Company ? $tenant->name : '',
        ];
    }
}
