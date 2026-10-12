<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\Detail;
use App\Models\Users\User;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use TresPontosTech\PanelApp\Filament\Pages\EditUserProfile;
use TresPontosTech\User\Enums\LifeMoment;

use function Pest\Laravel\getJson;
use function Pest\Livewire\livewire;

/**
 * Trocar a senha por qualquer caminho desconecta os aparelhos do app (UserObserver). O caso
 * real: a pessoa perde o celular, faz "esqueci a senha" no site, e o token de 90 dias do
 * aparelho perdido para de valer.
 */
function appTokensOf(User $user, int $count = 2): array
{
    return collect(range(1, $count))
        ->map(fn (int $device): string => $user->createToken('device-' . $device, ['employee'])->plainTextToken)
        ->all();
}

it('signs every device out after "forgot my password" on the site', function (): void {
    $user = standaloneSubscriber();
    [$lostPhone, $tablet] = appTokensOf($user);
    $token = Password::broker()->createToken($user);
    filament()->setCurrentPanel(FilamentPanel::User->value);

    livewire(ResetPassword::class, ['email' => $user->email, 'token' => $token])
        ->set('password', 'nova-senha-123')
        ->set('passwordConfirmation', 'nova-senha-123')
        ->call('resetPassword')
        ->assertHasNoErrors();

    expect(Hash::check('nova-senha-123', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);

    filament()->setCurrentPanel(null);
    getJson(route('api.v1.me.show'), ['Authorization' => 'Bearer ' . $lostPhone])->assertUnauthorized();
    resolve(Factory::class)->forgetGuards();
    getJson(route('api.v1.me.show'), ['Authorization' => 'Bearer ' . $tablet])->assertUnauthorized();
});

it('signs every device out when the password changes on the panel profile', function (): void {
    $employee = actingAsSubscribedEmployee();
    Detail::factory()->for($employee)->create();
    appTokensOf($employee);

    livewire(EditUserProfile::class)
        ->fillForm([
            'life_moment' => LifeMoment::cases()[0]->value,
            'main_motivation' => 'Sair das dívidas',
            'money_relationship' => 'Ainda me organizando',
            'plans_monthly_expenses' => 'Planejo todo mês',
            'tried_financial_strategies' => 'Já tentei planilhas',
            'password' => 'nova-senha-123',
            'passwordConfirmation' => 'nova-senha-123',
            'currentPassword' => 'password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('nova-senha-123', $employee->fresh()->password))->toBeTrue()
        ->and($employee->tokens()->count())->toBe(0);
});

it('signs every device out when someone else sets the password, as the admin does', function (): void {
    $user = standaloneSubscriber();
    appTokensOf($user, 3);

    $user->fresh()->update(['password' => 'definida-pelo-admin']);

    expect($user->tokens()->count())->toBe(0);
});

it('keeps the devices signed in when something other than the password changes', function (): void {
    $user = standaloneSubscriber();
    appTokensOf($user, 2);

    $user->fresh()->update(['name' => 'Outro Nome', 'email' => 'outro@exemplo.com']);

    expect($user->tokens()->count())->toBe(2);
});

it('does not touch other people tokens', function (): void {
    $user = standaloneSubscriber();
    $someoneElse = standaloneSubscriber();
    appTokensOf($someoneElse, 2);

    $user->fresh()->update(['password' => 'nova-senha-123']);

    expect($someoneElse->tokens()->count())->toBe(2);
});
