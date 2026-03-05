<?php

namespace TresPontosTech\Billing\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Database\Factories\PlanFactory;

class Plan extends Model
{
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

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class, 'billing_plan_id');
    }

    protected static function newFactory(): PlanFactory
    {
        return PlanFactory::new();
    }
}
