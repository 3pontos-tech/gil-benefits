<?php

namespace TresPontosTech\Billing\Core\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use TresPontosTech\Billing\Database\Factories\PriceFactory;

/**
 * @property int $id
 * @property int $billing_plan_id
 * @property string $billing_scheme
 * @property string $tiers_mode
 * @property string $type
 * @property int $unit_amount_decimal
 * @property bool $active
 * @property bool $default
 * @property string $provider_price_id
 * @property bool $whatsapp_enabled
 * @property bool $materials_enabled
 * @property int $monthly_appointments
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[UseFactory(PriceFactory::class)]
class Price extends Model
{
    /** @use HasFactory<PriceFactory> */
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

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'billing_plan_id');
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
