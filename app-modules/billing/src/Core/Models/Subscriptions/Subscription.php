<?php

namespace TresPontosTech\Billing\Core\Models\Subscriptions;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use TresPontosTech\Billing\Core\Models\Builders\SubscriptionBuilder;
use TresPontosTech\Billing\Core\Models\Collections\SubscriptionCollection;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Observers\SubscriptionQuotaAnchorObserver;
use TresPontosTech\Billing\Database\Factories\SubscriptionFactory;

/**
 * @property int $id
 * @property string $subscriptionable_type
 * @property string $subscriptionable_id
 * @property string $type
 * @property string $stripe_id
 * @property string $stripe_status
 * @property string|null $stripe_price
 * @property int|null $quantity
 * @property int|null $discount_percent
 * @property int|null $discount_amount_cents
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $quota_anchor_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[ObservedBy(SubscriptionQuotaAnchorObserver::class)]
#[UseFactory(SubscriptionFactory::class)]
#[UseEloquentBuilder(SubscriptionBuilder::class)]
#[CollectedBy(SubscriptionCollection::class)]
#[Table(name: 'billing_subscriptions')]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    /**
     * Status em que a assinatura passa a valer, nos três provedores: é o que Barte e
     * Virtu mandam no webhook de ativação e o que o Stripe usa fora de trial e
     * inadimplência. Mais estrito que {@see self::STATUSES_WITHOUT_ACCESS}, e de
     * propósito — decidir a âncora do ciclo de cota exige o instante em que a
     * assinatura começou a valer, não a lista do que não bloqueia acesso.
     */
    public const string STATUS_ACTIVE = 'active';

    public const array STATUSES_WITHOUT_ACCESS = ['pending', 'inactive', 'defaulter'];

    /**
     * Status em que a assinatura nunca chegou a valer, nos três provedores.
     *
     * Diferente de {@see self::STATUSES_WITHOUT_ACCESS}, que responde "tem acesso agora":
     * `defaulter`, `inactive` e `canceled` não dão acesso, mas já deram um dia — para elas o
     * `created_at` é uma âncora de ciclo legítima. Para as daqui, não é: a linha existe desde
     * o checkout e o `created_at` é anterior a qualquer pagamento.
     */
    public const array STATUSES_NEVER_ACTIVATED = ['pending', 'incomplete', 'incomplete_expired'];

    protected $fillable = [
        'subscriptionable_type',
        'subscriptionable_id',
        'type',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'discount_percent',
        'discount_amount_cents',
        'trial_ends_at',
        'ends_at',
        'quota_anchor_at',
    ];

    public function active(): bool
    {
        return $this->stripe_status === self::STATUS_ACTIVE && ! $this->ended();
    }

    /**
     * Any other status grants access only while a date says so: a trial still running,
     * or a paid period still running after a cancellation or a failed renewal. A
     * `canceled` row without an end date or a `trialing` row past its trial is refused.
     */
    public function valid(): bool
    {
        if ($this->deniesAccessByStatus()) {
            return false;
        }

        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at?->isFuture() ?? false;
    }

    public function onGracePeriod(): bool
    {
        return $this->ends_at?->isFuture() ?? false;
    }

    public function ended(): bool
    {
        return $this->ends_at !== null && ! $this->onGracePeriod();
    }

    private function deniesAccessByStatus(): bool
    {
        return in_array($this->stripe_status, self::STATUSES_WITHOUT_ACCESS, true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'quota_anchor_at' => 'datetime',
            'discount_percent' => 'integer',
            'discount_amount_cents' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo('subscriptionable');
    }

    /**
     * @return BelongsTo<Price, $this>
     */
    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class, 'stripe_price', 'provider_price_id');
    }

    /**
     * @return HasOneThrough<Plan, Price, $this>
     */
    public function plan(): HasOneThrough
    {
        return $this->hasOneThrough(
            Plan::class,
            Price::class,
            'provider_price_id',
            'id',
            'stripe_price',
            'billing_plan_id'
        );
    }
}
