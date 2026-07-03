<?php

namespace TresPontosTech\Billing\Core\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Database\Factories\PlanFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property BillingProviderEnum $provider
 * @property string|null $provider_product_id
 * @property int|null $trial_days
 * @property bool|null $has_generic_trial
 * @property bool|null $allow_promotion_codes
 * @property bool|null $collect_tax_ids
 * @property bool $active
 * @property string $slug
 * @property BillableTypeEnum $type
 * @property string|null $unit_label
 * @property string|null $statement_descriptor
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[UseFactory(PlanFactory::class)]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'billing_plans';

    protected $fillable = [
        'name',
        'description',
        'provider',
        'provider_product_id',
        'trial_days',
        'has_generic_trial',
        'allow_promotion_codes',
        'collect_tax_ids',
        'slug',
        'type',
        'unit_label',
        'active',
        'statement_descriptor',
    ];

    protected function casts(): array
    {
        return [
            'type' => BillableTypeEnum::class,
            'provider' => BillingProviderEnum::class,
            'has_generic_trial' => 'boolean',
            'allow_promotion_codes' => 'boolean',
            'collect_tax_ids' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Price, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(Price::class, 'billing_plan_id');
    }
}
