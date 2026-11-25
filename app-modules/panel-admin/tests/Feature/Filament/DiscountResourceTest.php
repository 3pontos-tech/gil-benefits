<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Discounts\Enums\DiscountGrantRevocationReasonEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Pages\CreateDiscount;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Pages\EditDiscount;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Pages\ListDiscounts;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\RelationManagers\GrantsRelationManager;

use function Pest\Livewire\livewire;

beforeEach(fn () => actingAsAdmin());

it('renders the discount list', function (): void {
    Discount::factory()->count(2)->create();

    livewire(ListDiscounts::class)
        ->assertOk()
        ->assertCanSeeTableRecords(Discount::all());
});

it('creates a discount', function (): void {
    livewire(CreateDiscount::class)
        ->fillForm([
            'name' => 'Desconto de teste',
            'slug' => 'teste-desconto',
            'percent_off' => 20,
            'audience' => PriceAudienceEnum::Standalone->value,
            'claim_window_days' => 90,
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Discount::class, [
        'name' => 'Desconto de teste',
        'slug' => 'teste-desconto',
        'percent_off' => 20,
        'audience' => PriceAudienceEnum::Standalone->value,
        'claim_window_days' => 90,
        'active' => true,
    ]);
});

it('refuses a percentage outside 1 to 100', function (int $percent): void {
    livewire(CreateDiscount::class)
        ->fillForm([
            'name' => 'Desconto inválido',
            'slug' => 'desconto-invalido',
            'percent_off' => $percent,
            'audience' => PriceAudienceEnum::Standalone->value,
            'claim_window_days' => 90,
        ])
        ->call('create')
        ->assertHasFormErrors(['percent_off']);
})->with([0, 101]);

it('activates the clau discount on edit', function (): void {
    $discount = Discount::query()->where('slug', 'clau-diagnostico')->sole();

    livewire(EditDiscount::class, ['record' => $discount->getKey()])
        ->fillForm(['active' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($discount->refresh()->active)->toBeTrue();
});

it('renders the grants of a discount', function (): void {
    $discount = Discount::factory()->create();
    $grants = DiscountGrant::factory()->count(2)->for($discount)->create();

    livewire(GrantsRelationManager::class, [
        'ownerRecord' => $discount,
        'pageClass' => EditDiscount::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords($grants);
});

it('revokes a grant manually', function (): void {
    $discount = Discount::factory()->create();
    $grant = DiscountGrant::factory()->for($discount)->create();

    livewire(GrantsRelationManager::class, [
        'ownerRecord' => $discount,
        'pageClass' => EditDiscount::class,
    ])
        ->callAction(TestAction::make('revoke')->table($grant));

    $grant->refresh();

    expect($grant->revoked_at)->not->toBeNull()
        ->and($grant->revoked_reason)->toBe(DiscountGrantRevocationReasonEnum::Manual);
});

it('hides the revoke action for a grant already revoked', function (): void {
    $discount = Discount::factory()->create();
    $revoked = DiscountGrant::factory()->revoked()->for($discount)->create();

    livewire(GrantsRelationManager::class, [
        'ownerRecord' => $discount,
        'pageClass' => EditDiscount::class,
    ])
        ->assertActionHidden(TestAction::make('revoke')->table($revoked));
});
