<?php

declare(strict_types=1);

use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\PanelApp\Filament\Widgets\UserCreditStatsWidget;

use function Pest\Livewire\livewire;

it('does not count a credit past its validity as available', function (): void {
    $employee = actingAsEmployee();
    $tenant = filament()->getTenant();
    $credit = fn (array $state): UserCredit => UserCredit::factory()->available()->create([
        'owner_id' => $employee->id,
        'holder_id' => $employee->id,
        'company_id' => $tenant->getKey(),
        ...$state,
    ]);

    $credit(['expires_at' => null]);
    $credit(['expires_at' => now()->subHour()]);

    $stats = collect(invade(livewire(UserCreditStatsWidget::class)->instance())->getStats())
        ->mapWithKeys(fn (Stat $stat): array => [$stat->getLabel() => $stat->getValue()]);

    expect($stats[__('panel-app::widgets.credit_stats.available')])->toBe(1);
});
