<?php

namespace TresPontosTech\Billing\Barte\Commands;

use Illuminate\Console\Command;
use TresPontosTech\Billing\Barte\BarteClient;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Plan;

class SyncBartePlans extends Command
{
    protected $signature = 'barte:play';

    protected $description = 'Playground para testar a API da Barte';

    public function handle(BarteClient $client): void
    {
        $response = $client->getPlans();

        /** @var list<array<string, mixed>> $bartePlans */
        $bartePlans = $response['content'] ?? $response;

        $plans = collect($bartePlans);

        $this->table(
            ['UUID', 'Título', 'Ativo', 'Métodos', 'Valores'],
            $plans->map(function (array $p): array {
                /** @var list<array<string, mixed>> $values */
                $values = $p['values'] ?? [];

                return [
                    $p['uuid'],
                    $p['title'],
                    $p['active'] ? 'sim' : 'não',
                    implode(', ', $p['acceptPaymentMethods'] ?? []),
                    collect($values)->map(fn (array $v): string => sprintf('%s: %s', $v['type'], $v['valuePerMonth']))->join(' | '),
                ];
            })
        );

        foreach ($plans as $bartePlan) {
            $plan = $this->persistPlan($bartePlan);

            /** @var list<array<string, mixed>> $planValues */
            $planValues = $bartePlan['values'] ?? [];

            collect($planValues)
                ->filter(fn (array $value): bool => $value['type'] === 'MONTHLY')
                ->each(fn (array $value) => $plan->prices()->updateOrCreate(
                    ['provider_price_id' => $bartePlan['uuid']],
                    [
                        'billing_scheme' => 'per_unit',
                        'tiers_mode' => 'not-selected',
                        'type' => 'recurring',
                        'unit_amount_decimal' => (int) round($value['valuePerMonth'] * 100),
                        'active' => $bartePlan['active'],
                        'default' => true,
                        'audience' => PriceAudienceEnum::Subsidized,
                        'metadata' => [],
                    ]
                ));

            $this->syncStandalonePrices($plan, $bartePlan);
        }

        $this->info('Planos sincronizados: ' . $plans->count());
    }

    /**
     * Valor cheio, para quem não tem empregador subsidiando. Vive aqui porque
     * não existe na Barte: lá só está cadastrado o valor subsidiado.
     */
    private const STANDALONE_PRICES = [
        'flamma-gold-barte' => 25000,
        'flamma-platinum-barte' => 30000,
    ];

    /**
     * @param  array<string, mixed>  $bartePlan
     */
    private function syncStandalonePrices(Plan $plan, array $bartePlan): void
    {
        $standalonePriceInCents = self::STANDALONE_PRICES[$plan->slug] ?? null;

        if ($standalonePriceInCents === null) {
            return;
        }

        $plan->prices()->updateOrCreate(
            // O sufixo fica: assinaturas ativas gravaram este id em stripe_price.
            ['provider_price_id' => $bartePlan['uuid'] . '-standalone-user'],
            [
                'billing_scheme' => 'per_unit',
                'tiers_mode' => 'not-selected',
                'type' => 'recurring',
                'unit_amount_decimal' => $standalonePriceInCents,
                'active' => $bartePlan['active'],
                'default' => false,
                'audience' => PriceAudienceEnum::Standalone,
                'metadata' => [],
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $bartePlan
     */
    private function persistPlan(array $bartePlan): Plan
    {
        return Plan::query()->updateOrCreate(
            [
                'provider' => BillingProviderEnum::Barte,
                'provider_product_id' => $bartePlan['uuid'],
            ],
            [
                'name' => str($bartePlan['title'])->title()->toString(),
                'description' => 'N/A',
                'trial_days' => null,
                'has_generic_trial' => false,
                'allow_promotion_codes' => false,
                'collect_tax_ids' => false,
                'slug' => str($bartePlan['title'])->slug() . '-barte',
                'type' => BillableTypeEnum::User,
                'unit_label' => 'seats',
                'active' => false,
                'statement_descriptor' => str($bartePlan['title'])->upper()->limit(22)->toString(),
            ]
        );
    }
}
