<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Discounts\Database\Factories\DiscountFactory;
use TresPontosTech\Discounts\Enums\DiscountCustomerTypeEnum;
use TresPontosTech\Discounts\Enums\DiscountDistributionEnum;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Enums\DiscountValueTypeEnum;

/**
 * @property string $id
 * @property string $slug
 * @property string $name
 * @property DiscountValueTypeEnum $value_type
 * @property int|null $percent_off
 * @property int|null $amount_off_cents
 * @property DiscountDurationEnum $duration
 * @property int|null $duration_cycles
 * @property DiscountDistributionEnum $distribution
 * @property string|null $code
 * @property DiscountCustomerTypeEnum $customer_type
 * @property PriceAudienceEnum|null $audience
 * @property list<string>|null $eligible_plan_slugs
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int|null $claim_window_days
 * @property int|null $max_redemptions
 * @property int|null $max_redemptions_per_customer
 * @property bool $first_subscription_only
 * @property bool $locks_plan
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(DiscountFactory::class)]
final class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'slug',
        'name',
        'value_type',
        'percent_off',
        'amount_off_cents',
        'duration',
        'duration_cycles',
        'distribution',
        'code',
        'customer_type',
        'audience',
        'eligible_plan_slugs',
        'starts_at',
        'ends_at',
        'claim_window_days',
        'max_redemptions',
        'max_redemptions_per_customer',
        'first_subscription_only',
        'locks_plan',
        'active',
    ];

    protected $attributes = [
        'value_type' => 'percent',
        'duration' => 'forever',
        'distribution' => 'grant',
        'customer_type' => 'person',
    ];

    protected static function booted(): void
    {
        self::saving(function (Discount $discount): void {
            $discount->normalizeCode();
            $discount->guardCoherentOffer();
        });
    }

    protected function casts(): array
    {
        return [
            'value_type' => DiscountValueTypeEnum::class,
            'percent_off' => 'integer',
            'amount_off_cents' => 'integer',
            'duration' => DiscountDurationEnum::class,
            'duration_cycles' => 'integer',
            'distribution' => DiscountDistributionEnum::class,
            'customer_type' => DiscountCustomerTypeEnum::class,
            'audience' => PriceAudienceEnum::class,
            'eligible_plan_slugs' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'claim_window_days' => 'integer',
            'max_redemptions' => 'integer',
            'max_redemptions_per_customer' => 'integer',
            'first_subscription_only' => 'boolean',
            'locks_plan' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<DiscountGrant, $this>
     */
    public function grants(): HasMany
    {
        return $this->hasMany(DiscountGrant::class);
    }

    /**
     * @return HasMany<DiscountRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(DiscountRedemption::class);
    }

    /**
     * @return HasMany<DiscountRedemption, $this>
     */
    public function countedRedemptions(): HasMany
    {
        return $this->redemptions()->counting();
    }

    public static function normalizeCodeValue(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $normalized = Str::upper((string) preg_replace('/\s+/', '', $code));

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeCode(): void
    {
        $this->code = self::normalizeCodeValue($this->code);
    }

    private function guardCoherentOffer(): void
    {
        $isPercent = $this->value_type === DiscountValueTypeEnum::Percent;

        throw_if($isPercent && ($this->percent_off === null || $this->percent_off < 1 || $this->percent_off > 100), InvalidArgumentException::class, 'Desconto percentual precisa de um valor entre 1 e 100.');

        throw_if($isPercent && $this->amount_off_cents !== null, InvalidArgumentException::class, 'Desconto percentual não aceita valor fixo.');

        throw_if(! $isPercent && ($this->amount_off_cents === null || $this->amount_off_cents < 1), InvalidArgumentException::class, 'Desconto em valor fixo precisa de um valor em centavos maior que zero.');

        throw_if(! $isPercent && $this->percent_off !== null, InvalidArgumentException::class, 'Desconto em valor fixo não aceita percentual.');

        $isRepeating = $this->duration === DiscountDurationEnum::Repeating;

        throw_if($isRepeating && ($this->duration_cycles === null || $this->duration_cycles < 1), InvalidArgumentException::class, 'Desconto repetido precisa de pelo menos um ciclo.');

        throw_if(! $isRepeating && $this->duration_cycles !== null, InvalidArgumentException::class, 'Somente desconto repetido aceita quantidade de ciclos.');

        $isCode = $this->distribution === DiscountDistributionEnum::Code;

        throw_if($isCode && $this->code === null, InvalidArgumentException::class, 'Desconto por código precisa de um código.');

        throw_if($isCode && $this->claim_window_days === null && $this->ends_at === null, InvalidArgumentException::class, 'Desconto por código precisa de janela de resgate ou data final.');

        throw_if(! $isCode && $this->code !== null, InvalidArgumentException::class, 'Somente desconto por código aceita código.');
    }
}
