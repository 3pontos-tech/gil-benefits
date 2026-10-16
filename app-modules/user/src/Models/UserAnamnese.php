<?php

namespace TresPontosTech\User\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use TresPontosTech\User\Database\Factories\UserAnamneseFactory;
use TresPontosTech\User\Enums\LifeMoment;

/**
 * @property string $id
 * @property string $user_id
 * @property LifeMoment|null $life_moment
 * @property string|null $main_motivation
 * @property string|null $money_relationship
 * @property string|null $plans_monthly_expenses
 * @property string|null $tried_financial_strategies
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 */
#[UseFactory(UserAnamneseFactory::class)]
class UserAnamnese extends Model
{
    /** @use HasFactory<UserAnamneseFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'user_id',
        'life_moment',
        'main_motivation',
        'money_relationship',
        'plans_monthly_expenses',
        'tried_financial_strategies',
    ];

    protected function casts(): array
    {
        return [
            'life_moment' => LifeMoment::class,
        ];
    }

    /**
     * As cinco respostas preenchidas. O app grava a anamnese aos poucos, então a linha pode
     * existir com respostas faltando; o painel só a considera concluída com as cinco.
     */
    public function isComplete(): bool
    {
        return filled($this->life_moment)
            && filled($this->main_motivation)
            && filled($this->money_relationship)
            && filled($this->plans_monthly_expenses)
            && filled($this->tried_financial_strategies);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
