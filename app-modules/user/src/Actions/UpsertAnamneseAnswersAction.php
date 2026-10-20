<?php

declare(strict_types=1);

namespace TresPontosTech\User\Actions;

use App\Models\Users\User;
use TresPontosTech\User\Models\UserAnamnese;

final readonly class UpsertAnamneseAnswersAction
{
    /**
     * Grava só as respostas recebidas, preservando as outras. É o salvamento campo a campo
     * do app: a primeira resposta cria a linha com as demais vazias, e `null` apaga uma
     * resposta. O wizard do painel continua usando SaveAnamneseAction, que grava as cinco.
     *
     * @param  array{life_moment?: string|null, main_motivation?: string|null, money_relationship?: string|null, plans_monthly_expenses?: string|null, tried_financial_strategies?: string|null}  $answers
     */
    public function handle(User $user, array $answers): UserAnamnese
    {
        return UserAnamnese::query()->updateOrCreate(['user_id' => $user->getKey()], $answers);
    }
}
