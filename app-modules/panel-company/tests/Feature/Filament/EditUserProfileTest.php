<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use Illuminate\Support\Facades\Hash;
use TresPontosTech\PanelCompany\Filament\Pages\EditUserProfile;

use function Pest\Livewire\livewire;

/**
 * Dono de empresa cadastrado sem `user_details`: a página criava a linha só com o telefone e
 * quebrava no `tax_id` obrigatório do banco.
 */
beforeEach(function (): void {
    $this->owner = actingAsCompanyOwner();
    $this->owner->detail()->delete();
});

it('asks for the CPF instead of failing when the owner has no details', function (): void {
    livewire(EditUserProfile::class)
        ->fillForm(['name' => 'Dona da Empresa'])
        ->call('save')
        ->assertHasFormErrors(['tax_id' => 'required']);

    expect($this->owner->detail()->exists())->toBeFalse();
});

it('saves the profile and the new password once the CPF is given', function (): void {
    livewire(EditUserProfile::class)
        ->fillForm([
            'name' => 'Dona da Empresa',
            'tax_id' => '52998224725',
            'password' => 'empresa-nova',
            'passwordConfirmation' => 'empresa-nova',
            'currentPassword' => 'password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $owner = $this->owner->fresh();

    expect($owner->name)->toBe('Dona da Empresa')
        ->and($owner->detail->tax_id)->toBe('52998224725')
        ->and(Hash::check('empresa-nova', $owner->password))->toBeTrue();
});

it('refuses a CPF that already belongs to someone else', function (): void {
    Detail::factory()->create(['tax_id' => '52998224725']);

    livewire(EditUserProfile::class)
        ->fillForm(['tax_id' => '52998224725'])
        ->call('save')
        ->assertHasFormErrors(['tax_id' => 'unique']);
});

it('keeps the CPF of an owner who already has details', function (): void {
    $detail = Detail::factory()->recycle($this->owner)->create(['tax_id' => '52998224725']);

    livewire(EditUserProfile::class)
        ->fillForm(['name' => 'Outro Nome'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->owner->fresh()->name)->toBe('Outro Nome')
        ->and($detail->fresh()->tax_id)->toBe('52998224725');
});
