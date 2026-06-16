<?php

declare(strict_types=1);

use TresPontosTech\Admin\Filament\Resources\Consultants\Pages\CreateConsultant;
use TresPontosTech\Admin\Filament\Resources\Consultants\Pages\EditConsultant;
use TresPontosTech\Admin\Filament\Resources\Consultants\Pages\ListConsultants;
use TresPontosTech\Consultants\Models\Consultant;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('renders the consultants list page', function (): void {
    livewire(ListConsultants::class)
        ->assertOk();
});

it('hides the preview tab on the create form', function (): void {
    livewire(CreateConsultant::class)
        ->assertDontSee('preview::tab', escape: false);
});

it('shows the avatar upload field to SuperAdmin on the edit form', function (): void {
    $consultant = Consultant::factory()->create();

    livewire(EditConsultant::class, ['record' => $consultant->getRouteKey()])
        ->assertFormFieldExists('avatar');
});
