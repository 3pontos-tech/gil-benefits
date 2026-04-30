<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Support\Str;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Pages\Tenancy\RegisterTenant;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->createOneQuietly();
    actingAs($this->user);
    filament()->setCurrentPanel(FilamentPanel::Company->value);
});

it('should be able to register a company without a user', function (): void {
    livewire(RegisterTenant::class)
        ->assertOk()
        ->fillForm([
            'name' => 'companyname',
            'tax_id' => '57.181.164/0001-80',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Company::class, [
        'name' => 'companyname',
        'slug' => Str::slug('companyname'),
        'tax_id' => '57181164000180',
    ]);
});

it('should assign the authenticated user as the company owner', function (): void {
    livewire(RegisterTenant::class)
        ->assertOk()
        ->fillForm([
            'name' => 'companyname',
            'tax_id' => '57.181.164/0001-80',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Company::class, [
        'name' => 'companyname',
        'slug' => Str::slug('companyname'),
        'tax_id' => '57181164000180',
    ]);
    assertDatabaseCount(Company::class, 1);

    expect(Company::query()->first()->owner->getKey())->toBe(auth()->user()->getKey())
        ->and(auth()->user()->isCompanyOwner())->toBeTrue();
});

describe('canView', function (): void {
    it('cannot access the register page when the company tenant is already subscribed', function (): void {
        $company = Company::factory()->for($this->user, 'owner')->create();
        $company->subscriptions()->create([
            'type' => 'company',
            'stripe_id' => 'sub_already_subscribed',
            'stripe_status' => 'active',
        ]);

        filament()->setTenant($company);

        expect(RegisterTenant::canView())->toBeFalse();
    });

    it('can access the register page when no tenant is set', function (): void {
        expect(RegisterTenant::canView())->toBeTrue();
    });
});

describe('validation tests', function (): void {
    test('name::field', function ($rule, $value): void {
        actingAs(User::factory()->createOneQuietly());
        filament()->setCurrentPanel(FilamentPanel::Company->value);
        livewire(RegisterTenant::class)
            ->assertOk()
            ->fillForm([
                'name' => $value,
            ])
            ->call('register')
            ->assertHasFormErrors(['name' => $rule]);
    })->with([
        'name' => ['required', ''],
    ]);

    test('tax_id::field', function ($rule, $value): void {
        if ($rule === 'unique') {
            $value = Company::factory()->createOne()->tax_id;
        }

        livewire(RegisterTenant::class)
            ->assertOk()
            ->fillForm([
                'tax_id' => $value,
            ])
            ->call('register')
            ->assertHasFormErrors(['tax_id' => $rule]);
    })->with([
        'tax_id' => ['required', ''],
        'unique' => ['unique', 'unique'],
    ]);
});
