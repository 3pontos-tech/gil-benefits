<?php

use TresPontosTech\Admin\Filament\Resources\Prices\Pages\ListPrices;
use TresPontosTech\Billing\Core\Models\Price;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    $this->prices = Price::factory(5)->create();
});

it('should render', function (): void {
    livewire(ListPrices::class)
        ->assertOk();
});

it('should list all prices', function (): void {
    livewire(ListPrices::class)
        ->assertOk()
        ->assertCanSeeTableRecords($this->prices)
        ->assertCountTableRecords($this->prices->count());
});

test('can not see trashed prices as default', function (): void {
    $trashedPrice = Price::factory(3)->trashed()->create();
    livewire(ListPrices::class)
        ->assertOk()
        ->assertCanSeeTableRecords($this->prices)
        ->assertCanNotSeeTableRecords($trashedPrice);

    livewire(ListPrices::class)
        ->assertOk()
        ->filterTable('trashed')
        ->assertCanSeeTableRecords($trashedPrice);
});
