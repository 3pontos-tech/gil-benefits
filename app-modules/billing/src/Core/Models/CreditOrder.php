<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\CreditOrderStatusEnum;
use TresPontosTech\Billing\Database\Factories\CreditOrderFactory;
use TresPontosTech\Company\Models\Company;

/**
 * @property string $id
 * @property BillingProviderEnum $provider
 * @property string|null $checkout_id
 * @property string $billable_type
 * @property string $billable_id
 * @property string $company_id
 * @property int $quantity
 * @property int $amount_cents
 * @property CreditOrderStatusEnum $status
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[UseFactory(CreditOrderFactory::class)]
class CreditOrder extends Model
{
    /** @use HasFactory<CreditOrderFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'provider',
        'checkout_id',
        'billable_type',
        'billable_id',
        'company_id',
        'quantity',
        'amount_cents',
        'status',
        'paid_at',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<UserCredit, $this>
     */
    public function credits(): HasMany
    {
        return $this->hasMany(UserCredit::class);
    }

    public function buyerName(): ?string
    {
        $billable = $this->billable;

        if ($billable instanceof Company || $billable instanceof User) {
            return $billable->name;
        }

        return null;
    }

    public function isPaid(): bool
    {
        return $this->status === CreditOrderStatusEnum::Paid;
    }

    protected function casts(): array
    {
        return [
            'provider' => BillingProviderEnum::class,
            'status' => CreditOrderStatusEnum::class,
            'quantity' => 'integer',
            'amount_cents' => 'integer',
            'paid_at' => 'datetime',
        ];
    }
}
