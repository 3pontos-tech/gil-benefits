<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Pages\Tenancy\EditTenantProfile;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $user = actingAsAdmin(FilamentPanel::Company);
    $this->anotherCompany = Company::factory()
        ->recycle($user)
        ->create();

    $this->anotherCompany->employees()->attach($user);
    filament()->setTenant($this->anotherCompany);

    $this->flammaCompany = Company::factory()
        ->recycle($user)
        ->state([
            'slug' => 'flamma-company',
        ])
        ->create();

    $this->flammaCompany->employees()->attach($user);
});

it('should redirect to available subscriptions if company is not flamma company', function (): void {
    $fakeDriver = new class implements BillingContract
    {
        public function ensureCustomerExists($tenant): void {}

        public function isSubscribed($tenant, string $planSlug): bool
        {
            return true;
        }

        public function getSubscriptionId($tenant): string
        {
            return 'fake_id_123';
        }

        public function getProviderName(): string
        {
            return 'fake_provider';
        }

        public function hasActivePlan(Company $company): bool
        {
            return true;
        }

        public function createCheckout(User|Company $billable, CheckoutData $data): string
        {
            return 'www.google.com';
        }

        public function getBillingPortalUrl(User|Company $billable, string $returnUrl, array $options = []): string
        {
            return 'billingPortal';
        }

        public function hasActiveSubscription(User|Company $billable): bool
        {
            return true;
        }

        public function cancelSubscription(User|Company $billable): void {}

        public function checkoutOpensInNewTab(): bool
        {
            return true;
        }
    };

    $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock) use ($fakeDriver) {
        $mock->makePartial();
        $mock->shouldReceive('getDriver')->andReturn($fakeDriver);
    }));

    $response = get(route('filament.company.pages.dashboard', ['tenant' => $this->anotherCompany->slug]))
        ->assertStatus(Response::HTTP_FOUND);

    $response->assertRedirect(route('filament.company.pages.available-subscriptions', ['tenant' => $this->anotherCompany->slug]));

    get(route('filament.company.pages.dashboard', ['tenant' => $this->flammaCompany->slug]))
        ->assertStatus(Response::HTTP_OK);
})->skipOnCI();

it('should render EditTenantProfile correctly if company has active plans', function (): void {
    $fakeDriver = new class implements BillingContract
    {
        public function ensureCustomerExists($tenant): void {}

        public function isSubscribed($tenant, string $planSlug): bool
        {
            return true;
        }

        public function getSubscriptionId($tenant): string
        {
            return 'fake_id_123';
        }

        public function getProviderName(): string
        {
            return 'fake_provider';
        }

        public function hasActivePlan(Company $company): bool
        {
            return true;
        }

        public function createCheckout(User|Company $billable, CheckoutData $data): string
        {
            return 'www.google.com';
        }

        public function getBillingPortalUrl(User|Company $billable, string $returnUrl, array $options = []): string
        {
            return 'billingPortal';
        }

        public function hasActiveSubscription(User|Company $billable): bool
        {
            return true;
        }

        public function cancelSubscription(User|Company $billable): void {}

        public function checkoutOpensInNewTab(): bool
        {
            return true;
        }
    };

    $this->instance(BillingManager::class, Mockery::mock(new BillingManager(app()), function ($mock) use ($fakeDriver) {
        $mock->makePartial();
        $mock->shouldReceive('getDriver')->andReturn($fakeDriver);
    }));

    Artisan::call('app:sync-subscription-to-flamma-company');
    $this->flammaCompany->refresh();
    filament()->setTenant($this->flammaCompany);

    livewire(EditTenantProfile::class, ['tenant' => $this->flammaCompany->slug])
        ->assertOk();
})->skipOnCI();
