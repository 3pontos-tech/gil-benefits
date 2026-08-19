<?php

declare(strict_types=1);

use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;
use TresPontosTech\Billing\Core\Enums\CreditOrderStatusEnum;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Clusters\Credits\CreditsCluster;
use TresPontosTech\PanelAdmin\Filament\Clusters\Credits\Resources\CreditOrders\Pages\ListCreditOrders;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

function creditOrder(array $attributes = []): CreditOrder
{
    return CreditOrder::factory()
        ->forCompany(Company::factory()->create())
        ->create($attributes);
}

it('lists credit orders', function (): void {
    $order = creditOrder(['quantity' => 4]);

    livewire(ListCreditOrders::class)
        ->assertCanSeeTableRecords([$order])
        ->assertSee($order->company->name);
});

it('filters the orders that were paid but never issued credits', function (): void {
    $unfulfilled = creditOrder(['status' => CreditOrderStatusEnum::Paid, 'paid_at' => now()]);
    $pending = creditOrder();

    livewire(ListCreditOrders::class)
        ->filterTable('unfulfilled')
        ->assertCanSeeTableRecords([$unfulfilled])
        ->assertCanNotSeeTableRecords([$pending]);
});

it('settles a pending order and issues the credits', function (): void {
    $order = creditOrder(['quantity' => 2]);

    livewire(ListCreditOrders::class)
        ->callTableAction('settle', $order);

    expect($order->refresh()->status)->toBe(CreditOrderStatusEnum::Paid)
        ->and(UserCredit::query()->where('credit_order_id', $order->getKey())->count())->toBe(2);
});

it('does not offer the settle action on an order already paid', function (): void {
    $order = creditOrder(['status' => CreditOrderStatusEnum::Paid, 'paid_at' => now()]);

    livewire(ListCreditOrders::class)
        ->assertTableActionHidden('settle', $order);
});

it('exposes both credit resources in the sidebar instead of hiding them behind the cluster', function (): void {
    // O padrão do Filament esconde componentes de cluster da navegação principal.
    // CreditsCluster::getNavigationItems() sobrescreve isso; se um upgrade mudar
    // o ponto de extensão, é aqui que aparece.
    $labels = collect(CreditsCluster::getNavigationItems())
        ->map(fn (NavigationItem $item): string => $item->getLabel())
        ->all();

    expect($labels)->toHaveCount(2)
        ->and($labels)->toContain(
            __('panel-admin::resources.credit_grants.navigation_label'),
            __('panel-admin::resources.credit_orders.navigation_label'),
        );
});

it('keeps the cluster tabs inside the page', function (): void {
    $page = new ListCreditOrders;

    expect($page->getSubNavigation())->toHaveCount(2)
        ->and($page->getSubNavigationPosition())->toBe(SubNavigationPosition::Top);
});
