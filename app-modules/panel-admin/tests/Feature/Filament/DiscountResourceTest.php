<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Discounts\Enums\DiscountDistributionEnum;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Enums\DiscountGrantRevocationReasonEnum;
use TresPontosTech\Discounts\Enums\DiscountValueTypeEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Models\DiscountRedemption;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Pages\CreateDiscount;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Pages\EditDiscount;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Pages\ListDiscounts;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\RelationManagers\GrantsRelationManager;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\RelationManagers\RedemptionsRelationManager;

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

it('starts a new discount with one use per customer', function (): void {
    livewire(CreateDiscount::class)
        ->assertFormSet(['max_redemptions_per_customer' => 1]);
});

it('refuses an end date before the start date', function (): void {
    livewire(CreateDiscount::class)
        ->fillForm([
            'name' => 'Janela invertida',
            'slug' => 'janela-invertida',
            'percent_off' => 10,
            'audience' => PriceAudienceEnum::Standalone->value,
            'claim_window_days' => 90,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->format('Y-m-d H:i:s'),
        ])
        ->call('create')
        ->assertHasFormErrors(['ends_at']);
});

it('stores a fixed amount in cents and drops the percentage', function (): void {
    livewire(CreateDiscount::class)
        ->fillForm([
            'name' => 'Desconto fixo',
            'slug' => 'desconto-fixo',
            'value_type' => DiscountValueTypeEnum::FixedAmount->value,
            'amount_off_cents' => 49.9,
            'audience' => PriceAudienceEnum::Standalone->value,
            'claim_window_days' => 90,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Discount::class, [
        'slug' => 'desconto-fixo',
        'value_type' => DiscountValueTypeEnum::FixedAmount->value,
        'amount_off_cents' => 4990,
        'percent_off' => null,
    ]);
});

it('clears the percentage when an edited discount becomes a fixed amount', function (): void {
    $discount = Discount::factory()->create(['percent_off' => 30]);

    livewire(EditDiscount::class, ['record' => $discount->getKey()])
        ->fillForm([
            'value_type' => DiscountValueTypeEnum::FixedAmount->value,
            'amount_off_cents' => 30,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($discount->refresh())
        ->percent_off->toBeNull()
        ->amount_off_cents->toBe(3000);
});

it('requires a code for code distribution and stores it normalized', function (): void {
    livewire(CreateDiscount::class)
        ->fillForm([
            'name' => 'Cupom digitado',
            'slug' => 'cupom-digitado',
            'distribution' => DiscountDistributionEnum::Code->value,
            'percent_off' => 15,
            'audience' => PriceAudienceEnum::Standalone->value,
            'claim_window_days' => 30,
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => 'required']);

    livewire(CreateDiscount::class)
        ->fillForm([
            'name' => 'Cupom digitado',
            'slug' => 'cupom-digitado',
            'distribution' => DiscountDistributionEnum::Code->value,
            'code' => ' verao 2026 ',
            'percent_off' => 15,
            'audience' => PriceAudienceEnum::Standalone->value,
            'claim_window_days' => 30,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Discount::class, [
        'slug' => 'cupom-digitado',
        'distribution' => DiscountDistributionEnum::Code->value,
        'code' => 'VERAO2026',
    ]);
});

it('requires the number of charges for a repeating duration', function (): void {
    livewire(CreateDiscount::class)
        ->fillForm([
            'name' => 'Repetido',
            'slug' => 'repetido',
            'percent_off' => 20,
            'duration' => DiscountDurationEnum::Repeating->value,
            'audience' => PriceAudienceEnum::Standalone->value,
            'claim_window_days' => 90,
        ])
        ->call('create')
        ->assertHasFormErrors(['duration_cycles' => 'required']);

    livewire(CreateDiscount::class)
        ->fillForm([
            'name' => 'Repetido',
            'slug' => 'repetido',
            'percent_off' => 20,
            'duration' => DiscountDurationEnum::Repeating->value,
            'duration_cycles' => 3,
            'audience' => PriceAudienceEnum::Standalone->value,
            'claim_window_days' => 90,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Discount::class, [
        'slug' => 'repetido',
        'duration' => DiscountDurationEnum::Repeating->value,
        'duration_cycles' => 3,
    ]);
});

it('warns when the gateway does not support the chosen duration', function (): void {
    livewire(CreateDiscount::class)
        ->fillForm(['duration' => DiscountDurationEnum::Once->value])
        ->assertSee('Duração não suportada pelo gateway de pagamento');
});

it('does not warn for a forever duration', function (): void {
    livewire(CreateDiscount::class)
        ->fillForm(['duration' => DiscountDurationEnum::Forever->value])
        ->assertDontSee('Duração não suportada pelo gateway de pagamento');
});

it('stores the eligible plans and saves an empty selection as all plans', function (): void {
    $plan = Plan::factory()->create([
        'type' => BillableTypeEnum::User,
        'active' => true,
        'slug' => 'plano-teste',
    ]);

    livewire(CreateDiscount::class)
        ->fillForm([
            'name' => 'Só um plano',
            'slug' => 'so-um-plano',
            'percent_off' => 10,
            'eligible_plan_slugs' => [$plan->slug],
            'audience' => PriceAudienceEnum::Standalone->value,
            'claim_window_days' => 90,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Discount::query()->where('slug', 'so-um-plano')->sole()->eligible_plan_slugs)
        ->toBe([$plan->slug]);

    livewire(CreateDiscount::class)
        ->fillForm([
            'name' => 'Todos os planos',
            'slug' => 'todos-os-planos',
            'percent_off' => 10,
            'eligible_plan_slugs' => [],
            'audience' => PriceAudienceEnum::Standalone->value,
            'claim_window_days' => 90,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Discount::query()->where('slug', 'todos-os-planos')->sole()->eligible_plan_slugs)->toBeNull();
});

it('shows the value, usage and limit of each discount in the list', function (): void {
    $discount = Discount::factory()->create(['percent_off' => 30, 'max_redemptions' => 10]);
    DiscountRedemption::factory()->count(2)->for($discount)->active()->create();
    DiscountRedemption::factory()->for($discount)->released()->create();

    livewire(ListDiscounts::class)
        ->assertTableColumnStateSet('discount_value', '30%', record: $discount)
        ->assertTableColumnStateSet('used_redemptions_count', 2, record: $discount)
        ->assertTableColumnStateSet('max_redemptions', 10, record: $discount);
});

it('renders the redemptions of a discount', function (): void {
    $discount = Discount::factory()->create();
    $redemptions = DiscountRedemption::factory()->count(2)->for($discount)->active()->create();

    livewire(RedemptionsRelationManager::class, [
        'ownerRecord' => $discount,
        'pageClass' => EditDiscount::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords($redemptions);
});

it('keeps the less important columns of the discount list hidden by default so the actions fit', function (): void {
    $component = livewire(ListDiscounts::class);
    $table = $component->instance()->getTable();

    expect($table->getColumn('name')->isToggledHiddenByDefault())->toBeFalse()
        ->and($table->getColumn('discount_value')->isToggledHiddenByDefault())->toBeFalse()
        ->and($table->getColumn('active')->isToggledHiddenByDefault())->toBeFalse()
        ->and($table->getColumn('slug')->isToggledHiddenByDefault())->toBeTrue()
        ->and($table->getColumn('created_at')->isToggledHiddenByDefault())->toBeTrue();
});
