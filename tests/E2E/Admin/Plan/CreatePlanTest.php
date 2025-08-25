<?php

use App\Enums\PlanTypeEnum;
use App\Models\Plans\Item;
use App\Models\Plans\Plan;
use App\Models\Users\User;

use Filament\Forms\Components\Repeater;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('should be able to create a Plan', function (): void {
    Repeater::fake();
    $admin = User::factory()->admin()->create();
    actingAs($admin);

    $page = visit('/admin');
    $page->click('Criar Plano');

    $page->type('form.name', 'plan-name');
    $page->type('form.price', '100');
    $page->select('form.type', PlanTypeEnum::Monthly->name);
    $page->type('form.hours_included', '10');
    $page->type('form.description', 'the description homie');
    $page->type('form.Items.0.name','Item name');
    $page->type('form.Items.0.price','100');
    $page->type('form.Items.0.type','item-type');
    $page->type('form.Items.0.quantity','2');
    $page->pressAndWaitFor('key-bindings-1',3);
    $page->assertSee('Criado');


    assertDatabaseCount(Plan::class, 1);
    assertDatabaseCount('plan_items', 1);

    assertDatabaseHas(Plan::class, [
        'name' => 'plan-name',
        'price' => 100,
        'type' => PlanTypeEnum::Monthly,
        'hours_included' => 10,
        'description' => 'the description homie',
    ]);
    $plan = Plan::query()->first();

    assertDatabaseHas(Item::class, [
        'plan_id' => $plan->getKey(),
        'name' => 'Item name',
        'price' => 100,
        'type' => 'item-type',
        'quantity' => 2,
    ]);
});
