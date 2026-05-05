<?php

namespace TresPontosTech\Billing\Barte\Commands;

use Illuminate\Console\Command;
use TresPontosTech\Billing\Barte\BarteClient;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;

class SyncBartePlans extends Command
{
    protected $signature = 'barte:play';

    protected $description = 'Playground para testar a API da Barte';

    public function handle(BarteClient $client): void
    {
        $response = $client->getPlans();

        $plans = collect($response['content'] ?? $response);

        $this->table(
            ['UUID', 'Título', 'Ativo', 'Métodos', 'Valores'],
            $plans->map(fn (array $p): array => [
                $p['uuid'],
                $p['title'],
                $p['active'] ? 'sim' : 'não',
                implode(', ', $p['acceptPaymentMethods'] ?? []),
                collect($p['values'] ?? [])->map(fn ($v): string => sprintf('%s: %s', $v['type'], $v['valuePerMonth']))->join(' | '),
            ])
        );

        foreach ($plans as $bartePlan) {
            $plan = $this->persistPlan($bartePlan);

            collect($bartePlan['values'] ?? [])
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
                        'metadata' => [],
                    ]
                ));
        }

        $this->info('Planos sincronizados: ' . $plans->count());
    }

    private function persistPlan(array $bartePlan): Plan
    {
        return Plan::query()->updateOrCreate(
            [
                'provider' => BillingProviderEnum::Barte,
                'provider_product_id' => $bartePlan['uuid'],
            ],
            [
                'name' => $bartePlan['title'] . 'barte',
                'description' => 'whatever description',
                'trial_days' => null,
                'has_generic_trial' => false,
                'allow_promotion_codes' => false,
                'collect_tax_ids' => false,
                'slug' => str($bartePlan['title'] . 'barte')->slug(),
                'type' => BillableTypeEnum::User,
                'unit_label' => 'seats',
                'active' => $bartePlan['active'],
                'statement_descriptor' => str($bartePlan['title'])->upper()->limit(22)->toString(),
            ]
        );
    }
}
