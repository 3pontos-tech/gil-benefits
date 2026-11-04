<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\PanelAdmin\Filament\Resources\Prices\Pages\EditPrice;
use TresPontosTech\PanelAdmin\Filament\Resources\Prices\Pages\ListPrices;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('offers no permanent delete on a trashed price', function (): void {
    $price = Price::factory()->create();
    $price->delete();

    livewire(EditPrice::class, ['record' => $price->getRouteKey()])
        ->assertActionDoesNotExist('forceDelete')
        ->assertActionExists('restore');

    livewire(ListPrices::class)
        ->filterTable('trashed', true)
        ->assertTableActionDoesNotExist('forceDelete')
        ->assertTableBulkActionDoesNotExist('forceDelete')
        ->assertTableActionExists('restore')
        ->assertTableBulkActionExists('restore');
});
