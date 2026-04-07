<?php

use App\Models\Users\Detail;
use App\Models\Users\User;
use TresPontosTech\Admin\Filament\Pages\EditUserProfile;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(fn () => actingAsAdmin());

it('should render', function (): void {
    livewire(EditUserProfile::class)
        ->assertOk();
});

it('should be able to edit profile', function (): void {
    livewire(EditUserProfile::class, ['record' => auth()->user()->getKey()])
        ->assertOk()
        ->fillForm([
            'name' => 'updated user',
            'email' => 'updated@doe.com',
            'tax_id' => '999.111.111-11',
            'document_id' => '22.333.444-5',
            'password' => 'corinthians',
            'currentPassword' => 'password',
            'passwordConfirmation' => 'corinthians',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, [
        'name' => 'updated user',
        'email' => 'updated@doe.com',
    ]);

    auth()->user()->fresh();

    assertDatabaseHas(Detail::class, [
        'user_id' => auth()->user()->getKey(),
        'tax_id' => '99911111111',
        'document_id' => '223334445',
    ]);

});
