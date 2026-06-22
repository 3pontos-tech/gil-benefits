<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
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
 * @property UserCreditStatusEnum $status
 * @property string|null $appointment_id
 * @property Carbon|null $transferred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class UserCredit extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'holder_id',
        'company_id',
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

    #[Scope]
    protected function forCompany(Builder $query, Company $company): Builder
    {
        return $query->where('company_id', $company->getKey());
    }

    #[Scope]
    protected function heldBy(Builder $query, ?Collection $userIds): Builder
    {
        if (! $userIds instanceof Collection) {
            return $query;
        }

        return $query->whereIn('holder_id', $userIds);
    }

    #[Scope]
    protected function ownedBy(Builder $query, Company $company): Builder
    {
        return $query->where('owner_id', $company->owner?->getKey());
    }

    protected static function newFactory(): UserCreditFactory
    {
        return UserCreditFactory::new();
    }
}
