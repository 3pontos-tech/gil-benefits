<?php

namespace TresPontosTech\Billing\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;

class BillingCustomer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'billing_customers';

    protected $fillable = [
        'billable_type',
        'billable_id',
        'provider',
        'provider_customer_id',
    ];

    protected function casts(): array
    {
        return [
            'provider' => BillingProviderEnum::class,
        ];
    }

    public static function getProviderCustomerId(Model $billable, BillingProviderEnum $provider): ?string
    {
        return static::query()
            ->where('billable_type', $billable->getMorphClass())
            ->where('billable_id', $billable->getKey())
            ->where('provider', $provider)
            ->value('provider_customer_id');
    }

    public static function getActiveProvider(Model $billable): ?BillingProviderEnum
    {
        return static::query()
            ->where('billable_type', $billable->getMorphClass())
            ->where('billable_id', $billable->getKey())->latest()
            ->value('provider');
    }
}
