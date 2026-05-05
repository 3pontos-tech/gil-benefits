<?php

declare(strict_types=1);

use TresPontosTech\App\Filament\Pages\UserSubscriptionPage;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;

use function Pest\Livewire\livewire;

it('renders the subscription page with available plans', function (): void {
    actingAsEmployee();

    $this->mock(PlanRepository::class, function ($mock): void {
        $mock->shouldReceive('getPlansFor')->andReturn(collect());
    });

    livewire(UserSubscriptionPage::class)
        ->assertOk();
});
