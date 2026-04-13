<?php

use App\Filament\FilamentPanel;
use Symfony\Component\HttpFoundation\Response;
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
    $response = get(route('filament.company.pages.dashboard', ['tenant' => $this->anotherCompany->slug]))
        ->assertStatus(Response::HTTP_FOUND);

    $response->assertRedirect(route('filament.company.pages.available-subscriptions', ['tenant' => $this->anotherCompany->slug]));

    get(route('filament.company.pages.dashboard', ['tenant' => $this->flammaCompany->slug]))
        ->assertStatus(Response::HTTP_OK);
});

it('should render EditTenantProfile correctly if company has active plans', function (): void {

    Artisan::call('app:sync-subscription-to-flamma-company');
    $this->flammaCompany->refresh();
    filament()->setTenant($this->flammaCompany);

    livewire(EditTenantProfile::class, ['tenant' => $this->flammaCompany->slug])
        ->assertOk();
});
