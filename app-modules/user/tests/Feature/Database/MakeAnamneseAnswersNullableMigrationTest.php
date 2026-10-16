<?php

declare(strict_types=1);

use TresPontosTech\User\Models\UserAnamnese;

it('refuses to make the answers required again while an anamnese is incomplete', function (): void {
    UserAnamnese::factory()->create(['money_relationship' => null]);

    $migration = require base_path('app-modules/user/database/migrations/2026_10_08_220918_make_user_anamneses_answers_nullable.php');

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Há 1 anamnese(s) incompleta(s)');
});
