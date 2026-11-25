<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Discounts\Database\Factories\DiscountFactory;

/**
 * @property string $id
 * @property string $slug
 * @property string $name
 * @property int $percent_off
 * @property PriceAudienceEnum $audience
 * @property int $claim_window_days
 * @property bool $active
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
        'percent_off',
        'audience',
        'claim_window_days',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'audience' => PriceAudienceEnum::class,
            'active' => 'boolean',
            'percent_off' => 'integer',
            'claim_window_days' => 'integer',
        ];
    }

    /**
     * @return HasMany<DiscountGrant, $this>
     */
    public function grants(): HasMany
    {
        return $this->hasMany(DiscountGrant::class);
    }
}
