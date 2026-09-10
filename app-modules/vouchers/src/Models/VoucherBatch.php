<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Vouchers\Database\Factories\VoucherBatchFactory;

/**
 * @property string $id
 * @property string $company_id
 * @property string $company_plan_id
 * @property string|null $created_by
 * @property string $name
 * @property int $quantity
 * @property Carbon|null $expires_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[UseFactory(VoucherBatchFactory::class)]
class VoucherBatch extends Model
{
    /** @use HasFactory<VoucherBatchFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'company_plan_id',
        'created_by',
        'name',
        'quantity',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function hasExpired(?Carbon $moment = null): bool
    {
        return $this->expires_at !== null && $this->expires_at->isBefore($moment ?? now());
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<CompanyPlan, $this>
     */
    public function companyPlan(): BelongsTo
    {
        return $this->belongsTo(CompanyPlan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<VoucherCode, $this>
     */
    public function codes(): HasMany
    {
        return $this->hasMany(VoucherCode::class);
    }

    /**
     * @return Builder<UserCredit>
     */
    public function creditsQuery(): Builder
    {
        return UserCredit::query()->whereIn(
            'voucher_redemption_id',
            VoucherRedemption::query()
                ->whereHas('code', fn (Builder $query) => $query->where('voucher_batch_id', $this->getKey()))
                ->select('id'),
        );
    }
}
