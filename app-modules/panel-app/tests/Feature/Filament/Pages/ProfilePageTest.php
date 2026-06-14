<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use TresPontosTech\App\Filament\Pages\ProfilePage;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Livewire\livewire;

it('renders the profile page for an authenticated tenant user', function (): void {
    actingAsEmployee();

    livewire(ProfilePage::class)->assertSuccessful();
});

it('prefills the form with the current user data', function (): void {
    $user = actingAsEmployee();

    livewire(ProfilePage::class)
        ->assertSet('data.name', $user->name)
        ->assertSet('data.email', $user->email);
});

it('updates the user account data', function (): void {
    $user = actingAsEmployee();
    Detail::factory()->recycle($user)->create();
    UserAnamnese::factory()->create(['user_id' => $user->id]);

    livewire(ProfilePage::class)
        ->fillForm(['name' => 'Novo Nome', 'email' => 'novo@email.com'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($user->refresh())
        ->name->toBe('Novo Nome')
        ->email->toBe('novo@email.com');
});

it('changes the password when the current one is correct', function (): void {
    $user = actingAsEmployee();
    Detail::factory()->recycle($user)->create();
    UserAnamnese::factory()->create(['user_id' => $user->id]);

    livewire(ProfilePage::class)
        ->fillForm([
            'password' => 'nova-senha-123',
            'passwordConfirmation' => 'nova-senha-123',
            'currentPassword' => 'password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('nova-senha-123', $user->refresh()->password))->toBeTrue();
});

it('saves the anamnese fields', function (): void {
    $user = actingAsEmployee();
    Detail::factory()->recycle($user)->create();

    livewire(ProfilePage::class)
        ->fillForm([
            'name' => $user->name,
            'email' => $user->email,
            'life_moment' => 'saver',
            'main_motivation' => 'Comprar um imóvel',
            'money_relationship' => 'Em evolução',
            'plans_monthly_expenses' => 'Planejo os fixos',
            'tried_financial_strategies' => 'Planilhas',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($user->refresh()->anamnese)
        ->not->toBeNull()
        ->main_motivation->toBe('Comprar um imóvel');
});

it('persists an uploaded avatar', function (): void {
    $user = actingAsEmployee();
    Detail::factory()->recycle($user)->create();
    UserAnamnese::factory()->create(['user_id' => $user->id]);

    livewire(ProfilePage::class)
        ->fillForm(['avatar' => [UploadedFile::fake()->image('avatar.png')]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->getMedia('user_avatar'))->toHaveCount(1);
});

it('links the profile under the current tenant route', function (): void {
    actingAsEmployee();
    $tenant = Filament::getTenant();

    expect(ProfilePage::getUrl())->toContain($tenant->slug);
});
