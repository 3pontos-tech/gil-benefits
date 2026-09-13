<?php

declare(strict_types=1);

use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\CreateCompany;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\EditCompany;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('keeps the slug when the company is renamed', function (): void {
    $company = Company::factory()->create([
        'user_id' => auth()->user()->getKey(),
        'name' => 'Incorporadora Alfa LTDA',
        'slug' => 'incorporadora-alfa-x7k2',
    ]);

    livewire(EditCompany::class, ['record' => $company->getRouteKey()])
        ->assertOk()
        ->fillForm(['name' => 'Incorporadora Alfa'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($company->fresh())
        ->slug->toBe('incorporadora-alfa-x7k2')
        ->name->toBe('Incorporadora Alfa');
});

it('still derives a slug from the name when creating', function (): void {
    livewire(CreateCompany::class)
        ->assertOk()
        ->fillForm([
            'user_id' => auth()->user()->getKey(),
            'name' => 'Construtora Beta',
            'tax_id' => '57.181.164/0001-80',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Company::query()->where('name', 'Construtora Beta')->sole()->slug)
        ->toStartWith('construtora-beta-');
});
