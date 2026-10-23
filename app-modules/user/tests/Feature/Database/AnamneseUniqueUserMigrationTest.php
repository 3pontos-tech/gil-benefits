<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TresPontosTech\User\Actions\UpsertAnamneseAnswersAction;

/**
 * @param  array<string, string|null>  $answers
 */
function rawAnamnese(User $user, string $updatedAt, array $answers): string
{
    $id = (string) Str::uuid7();

    DB::table('user_anamneses')->insert([
        'id' => $id,
        'user_id' => $user->id,
        'life_moment' => null,
        'main_motivation' => null,
        'money_relationship' => null,
        'plans_monthly_expenses' => null,
        'tried_financial_strategies' => null,
        ...$answers,
        'created_at' => $updatedAt,
        'updated_at' => $updatedAt,
    ]);

    return $id;
}

it('allows a single anamnese per person', function (): void {
    $user = User::factory()->create();
    rawAnamnese($user, '2026-10-01 10:00:00', ['main_motivation' => 'Primeira']);

    expect(fn (): string => rawAnamnese($user, '2026-10-01 10:00:01', ['main_motivation' => 'Segunda']))
        ->toThrow(UniqueConstraintViolationException::class);
});

/**
 * O outro salvamento grava logo depois da busca e antes do insert: é a janela da corrida.
 */
it('keeps one row when another save lands between the lookup and the insert', function (): void {
    $user = User::factory()->create();
    $competitorSaved = false;

    DB::listen(function (QueryExecuted $query) use ($user, &$competitorSaved): void {
        if ($competitorSaved || ! str_starts_with($query->sql, 'select') || ! str_contains($query->sql, 'user_anamneses')) {
            return;
        }

        $competitorSaved = true;
        rawAnamnese($user, now()->toDateTimeString(), ['life_moment' => 'messy']);
    });

    $anamnese = resolve(UpsertAnamneseAnswersAction::class)->handle($user, ['main_motivation' => 'Sair do cartão']);

    $rows = DB::table('user_anamneses')->where('user_id', $user->id)->get();

    expect($competitorSaved)->toBeTrue()
        ->and($rows)->toHaveCount(1)
        ->and($rows->first()->life_moment)->toBe('messy')
        ->and($rows->first()->main_motivation)->toBe('Sair do cartão')
        ->and($anamnese->id)->toBe($rows->first()->id);
});
