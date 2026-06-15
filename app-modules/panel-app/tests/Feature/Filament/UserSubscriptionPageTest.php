<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\App\Filament\Pages\UserSubscriptionPage;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Company\Models\Company;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Cache::flush();

    $owner = User::factory()->companyOwner()->create();
    $this->employee = User::factory()->employee()->create();

    $this->flamma = Company::factory()->recycle($owner)->create(['slug' => 'flamma-company']);
    $this->flamma->employees()->attach($this->employee->getKey());

    $this->company = Company::factory()->recycle(User::factory()->companyOwner()->create())->create();
    $this->company->employees()->attach($this->employee->getKey());

    $this->plan = Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User, 'name' => 'Plano Teste'])->create();

    // Standard price — shown outside flamma
    Price::factory()->for($this->plan, 'plan')->create([
        'provider_price_id' => 'price-standard',
        'unit_amount_decimal' => 19900,
        'metadata' => [],
    ]);

    // Flamma price — shown only inside flamma-company tenant
    Price::factory()->for($this->plan, 'plan')->create([
        'provider_price_id' => 'price-standalone-user',
        'unit_amount_decimal' => 35000,
        'metadata' => ['tenant' => 'flamma-company'],
    ]);
});

it('displays the flamma price when in the flamma-company tenant', function (): void {
    filament()->setCurrentPanel(FilamentPanel::User->value);
    $this->actingAs($this->employee);
    filament()->setTenant($this->flamma);

    livewire(UserSubscriptionPage::class)
        ->assertSee('350')
        ->assertDontSee('199');
});

it('displays the standard price when in a non-flamma tenant', function (): void {
    filament()->setCurrentPanel(FilamentPanel::User->value);
    $this->actingAs($this->employee);
    filament()->setTenant($this->company);

    livewire(UserSubscriptionPage::class)
        ->assertSee('199')
        ->assertDontSee('350');
});
