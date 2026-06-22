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
use Illuminate\Support\Collection;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Database\Factories\UserCreditFactory;
use TresPontosTech\Company\Models\Company;

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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

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
