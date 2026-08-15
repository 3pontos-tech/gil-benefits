<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Commands;

use Illuminate\Console\Command;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Plan;

/**
 * Cria plano e preço da Virtu no banco.
 *
 * Não chama a API: a Virtu não tem catálogo, o valor vai direto no payment-link
 * na hora do checkout. Por isso `provider_price_id` é um slug inventado por nós,
 * e não um id vindo do gateway como acontece na Stripe e na Barte.
 */
class CreateVirtuPlanCommand extends Command
{
    protected $signature = 'virtu:plan:create
        {slug : Identificador do plano, ex: platinum}
        {name : Nome exibido, ex: "Flamma Platinum"}
        {amount : Valor mensal em centavos, ex: 30000}
        {--type=company : company ou user}
        {--audience=subsidized : subsidized (empresa banca parte) ou standalone (valor cheio)}';

    protected $description = 'Cria plano e preço da Virtu no banco';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        $audience = PriceAudienceEnum::tryFrom((string) $this->option('audience'));

        if ($audience === null) {
            $this->error('--audience aceita apenas subsidized ou standalone.');

            return self::FAILURE;
        }

        $plan = Plan::query()->updateOrCreate(
            ['provider' => BillingProviderEnum::Virtu, 'slug' => $slug],
            [
                'name' => (string) $this->argument('name'),
                'description' => (string) $this->argument('name'),
                // A Virtu não tem produto, mas PlanEntity exige o campo preenchido.
                // Vale o slug: ninguém lê esse valor fora da própria entidade.
                'provider_product_id' => $slug,
                'type' => $this->option('type') === 'user' ? BillableTypeEnum::User : BillableTypeEnum::Company,
                'active' => true,
                // Colunas nullable no banco, mas PlanEntity exige bool.
                'has_generic_trial' => false,
                'allow_promotion_codes' => false,
                'collect_tax_ids' => false,
            ]
        );

        // Cada audiência é uma linha própria no mesmo plano, então o id precisa
        // distinguir as duas.
        $priceId = sprintf('%s-monthly-%s', $slug, $audience->value);

        $plan->prices()->updateOrCreate(
            ['provider_price_id' => $priceId],
            [
                'billing_scheme' => 'per_unit',
                'tiers_mode' => 'not-selected',
                'type' => 'recurring',
                'unit_amount_decimal' => (int) $this->argument('amount'),
                'active' => true,
                'default' => $audience === PriceAudienceEnum::Subsidized,
                'audience' => $audience,
                // A coluna é nullable, mas PriceEntity exige array.
                'metadata' => [],
            ]
        );

        $this->info(sprintf('Plano %s criado com o preço %s.', $slug, $priceId));

        return self::SUCCESS;
    }
}
