<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Grava o e-mail direto na tabela, como os cadastros antigos faziam antes do mutator.
 */
function legacyEmail(User $user, string $email): void
{
    DB::table('users')->where('id', $user->id)->update(['email' => $email]);
}

function runNormalizeUserEmails(): void
{
    $migration = require database_path('migrations/2026_10_07_223123_normalize_user_emails.php');
    $migration->up();
}

it('converts emails stored before the mutator to the canonical form', function (): void {
    $maria = User::factory()->create();
    legacyEmail($maria, ' Maria.Silva@Empresa.com');
    $ana = User::factory()->create(['email' => 'ana@x.com']);

    runNormalizeUserEmails();

    expect(DB::table('users')->where('id', $maria->id)->value('email'))->toBe('maria.silva@empresa.com')
        ->and(DB::table('users')->where('id', $ana->id)->value('email'))->toBe('ana@x.com');
});

it('leaves a colliding account untouched and logs it instead of failing', function (): void {
    Log::spy();
    $existing = User::factory()->create(['email' => 'ana@x.com']);
    $duplicate = User::factory()->create();
    legacyEmail($duplicate, 'Ana@X.com');

    runNormalizeUserEmails();

    expect(DB::table('users')->where('id', $duplicate->id)->value('email'))->toBe('Ana@X.com');

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $context === [
        'user_id' => $duplicate->id,
        'colliding_user_id' => $existing->id,
        'email' => 'ana@x.com',
    ]);
});

it('frees the canonical email from a deleted account so the active one keeps its login (scenario 1)', function (): void {
    Log::spy();
    $deleted = User::factory()->create(['email' => 'foo@x.com']);
    $deleted->delete();
    $active = User::factory()->create();
    legacyEmail($active, 'Foo@X.com');

    runNormalizeUserEmails();

    $marker = 'foo@x.com.deleted-' . $deleted->id;

    expect(DB::table('users')->where('id', $active->id)->value('email'))->toBe('foo@x.com')
        ->and(DB::table('users')->where('id', $deleted->id)->value('email'))->toBe($marker)
        ->and(User::query()->where('email', 'foo@x.com')->value('id'))->toBe($active->id);

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $context === [
        'user_id' => $active->id,
        'deleted_user_id' => $deleted->id,
        'original_email' => 'foo@x.com',
        'marker' => $marker,
    ]);
});

it('normalises the active account first when neither is canonical (scenario 2)', function (): void {
    Log::spy();
    $deleted = User::factory()->create();
    legacyEmail($deleted, 'FOO@x.com');
    $deleted->delete();
    $active = User::factory()->create();
    legacyEmail($active, 'Foo@x.com');

    expect($deleted->id < $active->id)->toBeTrue();

    runNormalizeUserEmails();

    expect(DB::table('users')->where('id', $active->id)->value('email'))->toBe('foo@x.com')
        ->and(DB::table('users')->where('id', $deleted->id)->value('email'))->toBe('FOO@x.com');

    Log::shouldNotHaveReceived('warning');
});

it('leaves a deleted account that collides with an active one as it is, without noise', function (): void {
    Log::spy();
    $active = User::factory()->create(['email' => 'foo@x.com']);
    $deleted = User::factory()->create();
    legacyEmail($deleted, 'Foo@X.com');
    $deleted->delete();

    runNormalizeUserEmails();

    expect(DB::table('users')->where('id', $active->id)->value('email'))->toBe('foo@x.com')
        ->and(DB::table('users')->where('id', $deleted->id)->value('email'))->toBe('Foo@X.com');

    Log::shouldNotHaveReceived('warning');
});

it('normalises a deleted account when the canonical form is free', function (): void {
    $deleted = User::factory()->create();
    legacyEmail($deleted, 'Antigo@X.com');
    $deleted->delete();

    runNormalizeUserEmails();

    expect(DB::table('users')->where('id', $deleted->id)->value('email'))->toBe('antigo@x.com');
});

it('lets the active account sign in after its email is freed from the deleted one', function (): void {
    $deleted = User::factory()->create(['email' => 'colaborador@empresa.com']);
    $deleted->delete();
    $active = standaloneSubscriber();
    legacyEmail($active, 'Colaborador@Empresa.com');

    runNormalizeUserEmails();

    Pest\Laravel\postJson(route('api.v1.auth.login'), [
        'email' => 'Colaborador@Empresa.com', 'password' => 'password', 'device_name' => 'Android',
    ])->assertOk()->assertJsonPath('data.user.id', $active->id);
});
