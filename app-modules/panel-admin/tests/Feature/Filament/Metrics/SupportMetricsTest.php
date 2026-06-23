<?php

declare(strict_types=1);

use TresPontosTech\PanelAdmin\Filament\Pages\Metrics;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\SupportTicketsByCategory;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\SupportTicketsBySector;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\SupportTicketsByStatus;
use TresPontosTech\PanelAdmin\Filament\Widgets\Metrics\SupportTicketStatsWidget;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();

    $statuses = [
        SupportTicketStatusEnum::Resolved,
        SupportTicketStatusEnum::Closed,
        SupportTicketStatusEnum::Pending,
        SupportTicketStatusEnum::InProgress,
    ];

    foreach ($statuses as $i => $status) {
        SupportTicket::query()->create([
            'protocol' => sprintf('SUP-2026-%04d', $i + 1),
            'category' => SupportTicketCategoryEnum::cases()[$i],
            'subject' => 'subject',
            'description' => 'description',
            'status' => $status,
            'environment' => 'testing',
        ]);
    }
});

it('renders the metrics page', function (): void {
    livewire(Metrics::class)->assertOk();
});

it('renders the support ticket metric widgets', function (): void {
    livewire(SupportTicketStatsWidget::class)->assertOk();
    livewire(SupportTicketsByStatus::class)->assertOk();
    livewire(SupportTicketsBySector::class)->assertOk();
    livewire(SupportTicketsByCategory::class)->assertOk();
});
