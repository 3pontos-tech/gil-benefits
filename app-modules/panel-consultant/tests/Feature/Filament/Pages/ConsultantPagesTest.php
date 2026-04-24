<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Consultants\Filament\Pages\ConsultantDashboard;
use TresPontosTech\Consultants\Filament\Pages\ConsultantSchedule;
use TresPontosTech\Consultants\Filament\Pages\EditConsultantProfile;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

it('renders the consultant dashboard page', function (): void {
    actingAsConsultant();

    livewire(ConsultantDashboard::class)
        ->assertOk();
});

it('allows the consultant to access the dashboard route', function (): void {
    actingAsConsultant();

    get(ConsultantDashboard::getUrl())
        ->assertSuccessful();
});

it('blocks a regular user from accessing the consultant dashboard', function (): void {
    $url = ConsultantDashboard::getUrl(panel: FilamentPanel::Consultant->value);

    actingAs(User::factory()->create());

    get($url)->assertForbidden();
});

it('keeps the consultant schedule page hidden via canAccess', function (): void {
    actingAsConsultant();

    expect(ConsultantSchedule::canAccess())->toBeFalse();
});

it('renders the consultant profile edit page', function (): void {
    actingAsConsultant();

    get(EditConsultantProfile::getUrl())
        ->assertSuccessful();
});

it('requires document_id when updating the consultant profile', function (): void {
    $consultant = actingAsConsultant();

    livewire(EditConsultantProfile::class)
        ->fillForm([
            'name' => $consultant->user->name,
            'email' => $consultant->user->email,
            'document_id' => '',
        ])
        ->call('save')
        ->assertHasFormErrors(['document_id' => 'required']);
});
