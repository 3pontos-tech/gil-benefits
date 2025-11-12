<?php

namespace TresPontosTech\Billing\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Price extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'billing_plan_prices';

    protected $fillable = [
        'billing_plan_id',
        'billing_scheme',
        'tiers_mode',
        'type',
        'unit_amount_decimal',
        'active',
        'provider_price_id',
        'default',
        'metadata',
    ];

    public function billingPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'billing_plan_id');
    }

    protected function casts(): array
    {
        return [
            'default' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
