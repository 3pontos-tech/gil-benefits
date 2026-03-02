<?php

use App\Filament\Admin\Clusters\Partners\Resources\Consultants\Pages\EditConsultant;
use Filament\Facades\Filament;
use TresPontosTech\Consultants\Models\Consultant;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    actingAsAdmin();
    $this->consultant = Consultant::factory()->createOne();
});

it('should render', function (): void {
    livewire(EditConsultant::class, ['record' => $this->consultant->getKey()])
        ->assertOk();
});

it('should be able to update the consultant', function (): void {
    livewire(EditConsultant::class, ['record' => $this->consultant->getKey()])
        ->assertOk()
        ->fillForm([
            'name' => 'updated_name',
            'email' => 'joe@doe.com',
            'short_description' => 'updated short description',
            'readme' => 'updated readme',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->consultant->refresh();

    expect($this->consultant->name)->toBe('updated_name')
        ->and($this->consultant->email)->toBe('joe@doe.com')
        ->and($this->consultant->short_description)->toBe('updated short description')
        ->and($this->consultant->readme)->toBe('updated readme');
});
