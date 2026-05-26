<?php

declare(strict_types=1);

use TresPontosTech\Admin\Filament\Resources\DepartmentCategories\Pages\CreateDepartmentCategory;
use TresPontosTech\Admin\Filament\Resources\DepartmentCategories\Pages\EditDepartmentCategory;
use TresPontosTech\Company\Models\DepartmentCategory;

use function Pest\Livewire\livewire;

beforeEach(fn () => actingAsAdmin());

describe('create', function (): void {
    it('prevents inserting a duplicate category name', function (): void {
        DepartmentCategory::factory()->create(['name' => 'RH']);

        livewire(CreateDepartmentCategory::class)
            ->fillForm(['name' => 'RH'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    });

    it('allows creating a category with a unique name', function (): void {
        livewire(CreateDepartmentCategory::class)
            ->fillForm(['name' => 'Financeiro'])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(DepartmentCategory::query()->where('name', 'Financeiro')->exists())->toBeTrue();
    });
});

describe('edit', function (): void {
    it('prevents renaming to a name already used by another category', function (): void {
        DepartmentCategory::factory()->create(['name' => 'TI']);
        $category = DepartmentCategory::factory()->create(['name' => 'RH']);

        livewire(EditDepartmentCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm(['name' => 'TI'])
            ->call('save')
            ->assertHasFormErrors(['name' => 'unique']);
    });

    it('allows saving the same name on the same record', function (): void {
        $category = DepartmentCategory::factory()->create(['name' => 'RH']);

        livewire(EditDepartmentCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm(['name' => 'RH'])
            ->call('save')
            ->assertHasNoFormErrors();
    });
});
