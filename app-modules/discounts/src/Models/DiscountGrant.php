<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use TresPontosTech\Discounts\Database\Factories\DiscountGrantFactory;
use TresPontosTech\Discounts\Enums\DiscountGrantRevocationReasonEnum;
use TresPontosTech\Discounts\Enums\DiscountGrantStatusEnum;

/**
 * @property string $id
 * @property string $discount_id
 * @property string|null $user_id
 * @property string $email
 * @property string|null $name
 * @property string|null $phone_number
 * @property string $source
 * @property string $external_ref
 * @property string|null $tax_id
 * @property string|null $consent_term_version
 * @property Carbon|null $consent_accepted_at
 * @property int $failed_tax_id_attempts
 * @property Carbon $expires_at
 * @property Carbon|null $claimed_at
 * @property string|null $plan_slug
 * @property Carbon|null $first_redeemed_at
 * @property Carbon|null $revoked_at
 * @property DiscountGrantRevocationReasonEnum|null $revoked_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Discount $discount
 * @property-read User|null $user
 */
#[UseFactory(DiscountGrantFactory::class)]
final class DiscountGrant extends Model
{
    /** @use HasFactory<DiscountGrantFactory> */
    use HasFactory;

    use HasUuids;
    use Prunable;

    protected $fillable = [
        'discount_id',
        'user_id',
        'email',
        'name',
        'phone_number',
        'source',
        'external_ref',
        'tax_id',
        'consent_term_version',
        'consent_accepted_at',
        'failed_tax_id_attempts',
        'expires_at',
        'claimed_at',
        'plan_slug',
        'first_redeemed_at',
        'revoked_at',
        'revoked_reason',
    ];

    protected function casts(): array
    {
        return [
            'tax_id' => 'encrypted',
            'consent_accepted_at' => 'datetime',
            'expires_at' => 'datetime',
            'claimed_at' => 'datetime',
            'first_redeemed_at' => 'datetime',
            'revoked_at' => 'datetime',
            'revoked_reason' => DiscountGrantRevocationReasonEnum::class,
            'failed_tax_id_attempts' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<DiscountRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(DiscountRedemption::class);
    }

    /**
     * @param  Builder<DiscountGrant>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('user_id')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    public function isPending(): bool
    {
        return $this->user_id === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture();
    }

    public function status(): DiscountGrantStatusEnum
    {
        if ($this->revoked_at !== null) {
            return DiscountGrantStatusEnum::Revoked;
        }

        if ($this->user_id !== null) {
            return DiscountGrantStatusEnum::Claimed;
        }

        if ($this->expires_at->lte(now())) {
            return DiscountGrantStatusEnum::Expired;
        }

        return DiscountGrantStatusEnum::Reserved;
    }

    /**
     * @return Builder<DiscountGrant>
     */
    public function prunable(): Builder
    {
        return self::query()
            ->whereNull('user_id')
            ->where('expires_at', '<=', now());
    }
}
