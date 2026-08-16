<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Database\Factories\UserCreditFactory;
use TresPontosTech\Company\Models\Company;

/**
 * @property string $id
 * @property string $owner_id
 * @property string $holder_id
 * @property string $company_id
 * @property string|null $grant_id
 * @property string|null $credit_order_id
 * @property UserCreditStatusEnum $status
 * @property string|null $appointment_id
 * @property Carbon|null $transferred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[UseFactory(UserCreditFactory::class)]
class UserCredit extends Model
{
    /** @use HasFactory<UserCreditFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    public const int PRICE_IN_CENTS = 15_000;

    public static function priceFor(int $quantity): int
    {
        return $quantity * self::PRICE_IN_CENTS;
    }

    protected $fillable = [
        'owner_id',
        'holder_id',
        'company_id',
        'grant_id',
        'credit_order_id',
        'status',
        'appointment_id',
        'transferred_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserCreditStatusEnum::class,
            'transferred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * @return BelongsTo<CreditOrder, $this>
     */
    public function creditOrder(): BelongsTo
    {
        return $this->belongsTo(CreditOrder::class);
    }

    /**
     * @return BelongsTo<CreditGrant, $this>
     */
    public function grant(): BelongsTo
    {
        return $this->belongsTo(CreditGrant::class, 'grant_id');
    }

    /**
     * @param  Builder<UserCredit>  $query
     * @return Builder<UserCredit>
     */
    #[Scope]
    protected function forCompany(Builder $query, Company $company): Builder
    {
        return $query->where('company_id', $company->getKey());
    }

    /**
     * @param  Builder<UserCredit>  $query
     * @param  Collection<int, string>|null  $userIds
     * @return Builder<UserCredit>
     */
    #[Scope]
    protected function heldBy(Builder $query, ?Collection $userIds): Builder
    {
        if (! $userIds instanceof Collection) {
            return $query;
        }

        return $query->whereIn('holder_id', $userIds);
    }

    /**
     * @param  Builder<UserCredit>  $query
     * @return Builder<UserCredit>
     */
    #[Scope]
    protected function ownedBy(Builder $query, Company $company): Builder
    {
        return $query->where('owner_id', $company->owner?->getKey());
    }
}
