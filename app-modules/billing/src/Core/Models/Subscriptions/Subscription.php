<?php

namespace TresPontosTech\Billing\Core\Models\Subscriptions;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription as BaseSubscriptionModel;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Observers\SubscriptionQuotaAnchorObserver;
use TresPontosTech\Billing\Database\Factories\SubscriptionFactory;
use TresPontosTech\Company\Models\Company;

/**
 * @property string $subscriptionable_type
 * @property string $subscriptionable_id
 * @property string $type
 * @property string $stripe_id
 * @property string $stripe_status
 * @property string|null $stripe_price
 * @property int|null $quantity
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $quota_anchor_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[ObservedBy(SubscriptionQuotaAnchorObserver::class)]
class Subscription extends BaseSubscriptionModel
{
    /**
     * Status em que a assinatura passa a valer, nos três provedores: é o que Barte e
     * Virtu mandam no webhook de ativação e o que o Stripe usa fora de trial e
     * inadimplência. Mais estrito que {@see self::STATUSES_WITHOUT_ACCESS}, e de
     * propósito — decidir a âncora do ciclo de cota exige o instante em que a
     * assinatura começou a valer, não a lista do que não bloqueia acesso.
     */
    public const string STATUS_ACTIVE = 'active';

    public const array STATUSES_WITHOUT_ACCESS = ['pending', 'inactive', 'defaulter'];

    protected $table = 'billing_subscriptions';

    public function active(): bool
    {
        return ! $this->deniesAccessByStatus() && parent::active();
    }

    public function valid(): bool
    {
        return ! $this->deniesAccessByStatus() && parent::valid();
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    protected function scopeGrantingAccess(Builder $query): Builder
    {
        return $query->whereNotIn('stripe_status', self::STATUSES_WITHOUT_ACCESS);
    }

    public static function grantsAccess(Company|User $billable, string $type): bool
    {
        return $billable->subscriptions
            ->where('type', $type)
            ->contains(fn (self $subscription): bool => $subscription->valid());
    }

    private function deniesAccessByStatus(): bool
    {
        return in_array($this->stripe_status, self::STATUSES_WITHOUT_ACCESS, true);
    }

    /**
     * Declarado como método, e não pelo atributo `#[UseFactory]`, porque o Cashier
     * sobrescreve `newFactory()` no model pai para devolver a factory dele.
     *
     * O `#[UseFactory]` só é lido dentro da implementação padrão de `newFactory()`, que
     * nunca chega a rodar aqui — então o atributo era ignorado em silêncio e
     * `Subscription::factory()` construía o model do Cashier, com tabela `subscriptions`
     * e chave de dono `company_id`.
     *
     * @return SubscriptionFactory
     */
    protected static function newFactory()
    {
        return SubscriptionFactory::new();
    }

    /**
     * Mesclado com o `$casts` do Cashier, que já cobre `ends_at` e `trial_ends_at`.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quota_anchor_at' => 'datetime',
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
