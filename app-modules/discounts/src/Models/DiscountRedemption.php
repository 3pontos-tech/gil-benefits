<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use TresPontosTech\Discounts\Database\Factories\DiscountRedemptionFactory;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Enums\DiscountValueTypeEnum;

/**
 * @property string $id
 * @property string $discount_id
 * @property string|null $discount_grant_id
 * @property string $billable_type
 * @property string $billable_id
 * @property string $plan_slug
 * @property string $subscription_external_id
 * @property DiscountRedemptionStatusEnum $status
 * @property DiscountValueTypeEnum $value_type
 * @property int|null $percent_off
 * @property int|null $amount_off_cents
 * @property DiscountDurationEnum $duration
 * @property int|null $duration_cycles
 * @property int $list_amount_cents
 * @property int $discounted_amount_cents
 * @property int $cycles_applied
 * @property Carbon|null $reserved_until
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Discount $discount
 * @property-read DiscountGrant|null $grant
 */
#[UseFactory(DiscountRedemptionFactory::class)]
final class DiscountRedemption extends Model
{
    /** @use HasFactory<DiscountRedemptionFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'discount_id',
        'discount_grant_id',
        'billable_type',
        'billable_id',
        'plan_slug',
        'subscription_external_id',
        'status',
        'value_type',
        'percent_off',
        'amount_off_cents',
        'duration',
        'duration_cycles',
        'list_amount_cents',
        'discounted_amount_cents',
        'cycles_applied',
        'reserved_until',
        'confirmed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DiscountRedemptionStatusEnum::class,
            'value_type' => DiscountValueTypeEnum::class,
            'percent_off' => 'integer',
            'amount_off_cents' => 'integer',
            'duration' => DiscountDurationEnum::class,
            'duration_cycles' => 'integer',
            'list_amount_cents' => 'integer',
            'discounted_amount_cents' => 'integer',
            'cycles_applied' => 'integer',
            'reserved_until' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Discount, $this>
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * @return BelongsTo<DiscountGrant, $this>
     */
    public function grant(): BelongsTo
    {
        return $this->belongsTo(DiscountGrant::class, 'discount_grant_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }
}
