<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\User\Support\EmailAddress;

it('trims and lowercases an email', function (?string $input, ?string $expected): void {
    expect(EmailAddress::normalize($input))->toBe($expected);
})->with([
    'mixed case' => ['Maria.Silva@Empresa.COM', 'maria.silva@empresa.com'],
    'surrounding spaces' => ['  joao@x.com ', 'joao@x.com'],
    'accented' => ['ÉRICA@X.COM', 'érica@x.com'],
    'already canonical' => ['ana@x.com', 'ana@x.com'],
    'null' => [null, null],
]);

it('stores every user email in the canonical form', function (): void {
    $user = User::factory()->create(['email' => '  Maria.Silva@Empresa.com ']);

    expect($user->fresh()->email)->toBe('maria.silva@empresa.com');

    $user->update(['email' => 'Outro@Empresa.com']);

    expect($user->fresh()->email)->toBe('outro@empresa.com');
});
