<?php

namespace TresPontosTech\User\Actions;

use App\Models\Users\User;
use TresPontosTech\User\Models\UserAnamnese;

readonly class SaveAnamneseAction
{
    /**
     * @param  array{
     *     life_moment: string,
     *     main_motivation: string,
     *     money_relationship: string,
     *     plans_monthly_expenses: bool,
     *     tried_financial_strategies: bool,
     *     financial_strategies_description: string|null,
     * }  $data
     */
    public function handle(User $user, array $data): UserAnamnese
    {
        return UserAnamnese::query()->updateOrCreate(['user_id' => $user->getKey()], $data);
    }
}
