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
            'tax_id' => '99999999999999',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Company::class, [
        'name' => 'companyname',
        'slug' => Str::slug('companyname'),
        'tax_id' => '99.999.999/9999-99',
    ]);
});

it('should assign the authenticated user as the company owner', function (): void {
    livewire(RegisterTenant::class)
        ->assertOk()
        ->fillForm([
            'name' => 'companyname',
            'tax_id' => '99.999.999/9999-99',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Company::class, [
        'name' => 'companyname',
        'slug' => Str::slug('companyname'),
        'tax_id' => '99.999.999/9999-99',
    ]);
    assertDatabaseCount(Company::class, 1);

    expect(Company::query()->first()->owner->getKey())->toBe(auth()->user()->getKey())
        ->and(auth()->user()->isCompanyOwner())->toBeTrue();
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
