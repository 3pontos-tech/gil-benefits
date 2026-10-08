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
