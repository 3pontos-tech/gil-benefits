<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Pages\Tenancy\RegisterTenant;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->user = User::factory()->companyOwner()->create();
    $this->company = Company::factory()->recycle($this->user)->create();
    $this->company->employees()->attach($this->user->getKey());

    filament()->setCurrentPanel(FilamentPanel::Company->value);
    actingAs($this->user);
    filament()->setTenant($this->company);
});

it('offers registration to a company with no subscription', function (): void {
    expect(RegisterTenant::canView())->toBeTrue();
});

it('hides registration from a company with an active subscription', function (): void {
    $this->company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'checkout_pago_' . uniqid(),
        'stripe_status' => 'active',
    ]);

    expect(RegisterTenant::canView())->toBeFalse();
});

it('hides registration when a paid subscription follows an abandoned checkout', function (): void {
    $this->company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'checkout_abandonado_' . uniqid(),
        'stripe_status' => 'pending',
    ]);

    $this->company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'checkout_pago_' . uniqid(),
        'stripe_status' => 'active',
    ]);

    expect(RegisterTenant::canView())->toBeFalse();
});
