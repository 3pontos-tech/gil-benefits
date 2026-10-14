<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Filament\Shared\Pages\LoginPage;
use App\Models\Users\Detail;
use App\Models\Users\User;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use TresPontosTech\PanelAdmin\Filament\Pages\EditUserProfile as AdminEditUserProfile;
use TresPontosTech\PanelAdmin\Filament\Resources\Users\Pages\EditUser;
use TresPontosTech\PanelApp\Filament\Pages\EditUserProfile;
use TresPontosTech\User\Actions\RevokeAppTokensAction;
use TresPontosTech\User\Enums\LifeMoment;

use function Pest\Laravel\getJson;
use function Pest\Livewire\livewire;

/**
 * Trocar a senha por um dos caminhos reais desconecta os aparelhos do app (RevokeAppTokensAction).
 * O caso real: a pessoa perde o celular, faz "esqueci a senha" no site, e o token de 90 dias do
 * aparelho perdido para de valer. Regravar o hash no login (rehash) não é troca de senha.
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

it('keeps the devices when the panel profile is saved without a new password', function (): void {
    $employee = actingAsSubscribedEmployee();
    Detail::factory()->for($employee)->create();
    appTokensOf($employee);

    livewire(EditUserProfile::class)
        ->fillForm([
            'name' => 'Outro Nome',
            'life_moment' => LifeMoment::cases()[0]->value,
            'main_motivation' => 'Sair das dívidas',
            'money_relationship' => 'Ainda me organizando',
            'plans_monthly_expenses' => 'Planejo todo mês',
            'tried_financial_strategies' => 'Já tentei planilhas',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($employee->fresh()->name)->toBe('Outro Nome')
        ->and($employee->tokens()->count())->toBe(2);
});

it('signs every device out when the password changes on another panel profile, through the shared page', function (): void {
    $admin = actingAsAdmin();
    Detail::factory()->for($admin)->create();
    appTokensOf($admin);

    livewire(AdminEditUserProfile::class, ['record' => $admin->getKey()])
        ->fillForm([
            'password' => 'nova-senha-123',
            'passwordConfirmation' => 'nova-senha-123',
            'currentPassword' => 'password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($admin->tokens()->count())->toBe(0);
});

it('signs every device out when the admin sets someone else\'s password', function (): void {
    actingAsAdmin();
    $user = standaloneSubscriber();
    Detail::factory()->for($user)->create();
    appTokensOf($user, 3);

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->fillForm(['password' => 'definida-pelo-admin'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('definida-pelo-admin', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);
});

it('keeps the devices when the admin edits someone without touching the password', function (): void {
    actingAsAdmin();
    $user = standaloneSubscriber();
    Detail::factory()->for($user)->create();
    appTokensOf($user, 2);

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->fillForm(['name' => 'Nome Corrigido'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->tokens()->count())->toBe(2);
});

/**
 * Alguém sobe o BCRYPT_ROUNDS: no próximo login do painel o hash é regravado (`rehash_on_login`).
 * Isso não é troca de senha e não pode desconectar o app de quem só entrou no painel.
 */
it('keeps the app signed in when the panel login rehashes the password', function (): void {
    $admin = User::factory()->admin()->create();
    $oldHash = $admin->fresh()->password;
    appTokensOf($admin, 2);
    config()->set('hashing.bcrypt.rounds', 5);
    app('hash')->forgetDrivers();
    filament()->setCurrentPanel(filament()->getPanel('admin'));

    expect(Hash::needsRehash($oldHash))->toBeTrue();

    livewire(LoginPage::class)
        ->fillForm(['email' => $admin->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect($admin->fresh()->password)->not->toBe($oldHash)
        ->and($admin->tokens()->count())->toBe(2);
});

it('ignores a token to keep that belongs to someone else', function (): void {
    $user = standaloneSubscriber();
    $someoneElse = standaloneSubscriber();
    appTokensOf($user, 2);
    $foreignToken = $someoneElse->createToken('Android', ['employee'])->accessToken;

    resolve(RevokeAppTokensAction::class)->handle($user, keep: $foreignToken);

    expect($user->tokens()->count())->toBe(0)
        ->and($someoneElse->tokens()->count())->toBe(1);
});
