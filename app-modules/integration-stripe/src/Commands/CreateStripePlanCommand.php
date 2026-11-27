<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\Commands;

use Illuminate\Console\Command;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\IntegrationStripe\StripeApi;

/**
 * Cria plano e preço no banco e, para pessoa física, o produto e o preço no Stripe.
 *
 * O sentido é sempre banco → Stripe. O plano de empresa não ganha preço no Stripe: o
 * valor por assento sai do `SeatPricingTierEnum` e vai inline no checkout, então o
 * `provider_price_id` dele é um id nosso.
 *
 * Preço no Stripe é imutável. Um valor novo cria outro preço e desativa a linha antiga,
 * que continua existindo para as assinaturas que já apontam para ela.
 *
 * A soft-deleted plan with the same slug (left by Cashier) is brought back as a new plan:
 * the unique (provider, slug) index still counts it, and its old product and prices are
 * not ours to sell anymore, so they are retired instead of reused.
 */
class CreateStripePlanCommand extends Command
{
    protected $signature = 'stripe:plan:create
        {slug : Identificador do plano, ex: platinum}
        {name : Nome exibido, ex: "Flamma Platinum"}
        {amount : Valor mensal em centavos, ex: 30000}
        {--type=user : company ou user}
        {--audience=standalone : subsidized (empresa banca parte) ou standalone (valor cheio)}
        {--monthly-appointments=1 : Consultas incluídas na cota mensal}';

    protected $description = 'Cria plano e preço do Stripe, do banco para o Stripe';

    public function handle(StripeApi $api): int
    {
        $slug = (string) $this->argument('slug');
        $name = (string) $this->argument('name');
        $amount = (int) $this->argument('amount');
        $audience = PriceAudienceEnum::tryFrom((string) $this->option('audience'));
        $isCompany = $this->option('type') === 'company';

        if ($audience === null) {
            $this->error('--audience aceita apenas subsidized ou standalone.');

            return self::FAILURE;
        }

        $plan = Plan::query()->withTrashed()->firstOrNew(['provider' => BillingProviderEnum::Stripe, 'slug' => $slug]);

        if ($plan->trashed()) {
            $plan->prices()->update(['active' => false]);
            $plan->provider_product_id = null;
            $plan->deleted_at = null;
        }

        $plan->fill([
            'name' => $name,
            'description' => $name,
            'type' => $isCompany ? BillableTypeEnum::Company : BillableTypeEnum::User,
            'active' => true,
            'has_generic_trial' => false,
            'allow_promotion_codes' => false,
            'collect_tax_ids' => false,
        ]);
        $plan->provider_product_id ??= $isCompany ? $slug : $api->createProduct($name, ['plan_slug' => $slug]);
        $plan->save();

        $current = $plan->prices()
            ->where('audience', $audience)
            ->where('active', true)
            ->first();

        if ($current instanceof Price && (int) $current->unit_amount_decimal === $amount) {
            $this->info(sprintf('Plano %s já tem o preço %s.', $slug, $current->provider_price_id));

            return self::SUCCESS;
        }

        $current?->update(['active' => false]);

        $priceId = $isCompany
            ? sprintf('%s-monthly-%s-%d', $slug, $audience->value, $amount)
            : $api->createMonthlyPrice(
                productId: $plan->provider_product_id,
                unitAmount: $amount,
                currency: (string) config('stripe.currency'),
                metadata: ['plan_slug' => $slug, 'audience' => $audience->value],
            );

        $plan->prices()->create([
            'provider_price_id' => $priceId,
            'billing_scheme' => 'per_unit',
            'tiers_mode' => 'not-selected',
            'type' => 'recurring',
            'unit_amount_decimal' => $amount,
            'active' => true,
            'default' => $audience === PriceAudienceEnum::Subsidized,
            'audience' => $audience,
            'monthly_appointments' => (int) $this->option('monthly-appointments'),
            'metadata' => [],
        ]);

        $this->info(sprintf('Plano %s criado com o preço %s.', $slug, $priceId));

        return self::SUCCESS;
    }
}
