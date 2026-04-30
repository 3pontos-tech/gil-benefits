<?php

namespace TresPontosTech\Billing\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use TresPontosTech\Billing\Database\Factories\PriceFactory;

/**
 * @property int $monthly_appointments
 */
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
        'whatsapp_enabled',
        'materials_enabled',
        'monthly_appointments',
        'metadata',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'billing_plan_id');
    }

    protected static function newFactory(): PriceFactory
    {
        return PriceFactory::new();
    }

    protected function casts(): array
    {
        return [
            'default' => 'boolean',
            'active' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'materials_enabled' => 'boolean',
            'unit_amount_decimal' => 'integer',
            'monthly_appointments' => 'integer',
            'metadata' => 'array',
        ];
    }
}
