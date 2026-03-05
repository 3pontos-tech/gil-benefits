<?php

use Filament\Facades\Filament;
use TresPontosTech\Admin\Filament\Resources\Consultants\Pages\ListConsultants;
use TresPontosTech\Consultants\Models\Consultant;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(ListConsultants::class)
        ->assertOk();
});

it('should render all consultants', function (): void {
    $consultants = Consultant::factory()->count(5)->create();

    livewire(ListConsultants::class)
        ->assertOk()
        ->assertCanSeeTableRecords($consultants);
});

it('should not list soft deleted consultants', function (): void {
    $consultants = Consultant::factory()->count(5)->create();
    $softDeleted = Consultant::factory()->count(5)->trashed()->create();

    livewire(ListConsultants::class)
        ->assertOk()
        ->assertCanSeeTableRecords($consultants)
        ->assertCanNotSeeTableRecords($softDeleted);
});
