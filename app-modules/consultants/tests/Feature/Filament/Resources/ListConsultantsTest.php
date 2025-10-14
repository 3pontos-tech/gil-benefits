<?php

use App\Filament\Admin\Clusters\Partners\Resources\Consultants\Pages\ListConsultants;
use App\Models\Users\User;
use Filament\Facades\Filament;
use TresPontosTech\Consultants\Models\Consultant;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    actingAs(User::factory()->admin()->create());
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
