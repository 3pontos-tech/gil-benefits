<?php

use Filament\Facades\Filament;
use TresPontosTech\Admin\Filament\Resources\Consultants\Pages\EditConsultant;
use TresPontosTech\Admin\Filament\Resources\Consultants\RelationManagers\SchedulesRelationManager;
use TresPontosTech\Consultants\Models\Consultant;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    actingAsSuperAdmin();
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
            'socials_urls' => [
                'linkedin' => 'https://www.linkedin.com/in/',
                'instagram' => 'https://www.instagram.com/',
                'facebook' => 'https://www.facebook.com/',
                'twitter' => 'https://www.twitter.com/',
                'youtube' => 'https://www.youtube.com/',
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->consultant->refresh();

    expect($this->consultant->name)->toBe('updated_name')
        ->and($this->consultant->email)->toBe('joe@doe.com')
        ->and($this->consultant->short_description)->toBe('updated short description')
        ->and($this->consultant->readme)->toBe('updated readme');
});

test('schedules relation manager', function (): void {
    livewire(SchedulesRelationManager::class, [
        'ownerRecord' => $this->consultant,
        'pageClass' => EditConsultant::class,
    ])
        ->assertOk();
});
