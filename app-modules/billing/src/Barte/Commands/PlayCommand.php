<?php

namespace TresPontosTech\Billing\Barte\Commands;

use Illuminate\Console\Command;
use TresPontosTech\Billing\Barte\BarteClient;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;

class PlayCommand extends Command
{
    protected $signature = 'barte:play';

    protected $description = 'Playground para testar a API da Barte';

    public function handle(BarteClient $client): void
    {
        $response = $client->post('/v2/payment-links', [
            'type'             => 'SUBSCRIPTION',
            'scheduledDate'    => now()->addDay()->toDateString(),
            'uuidSellerClient' => '76313d30-b0c2-4111-9832-c8d6ac9a4fb6',
            'paymentSubscription' => [
                'idPlan'        => 5810,
                'type'          => 'MONTHLY',
                'valuePerMonth' => 1,
            ],
            'paymentMethods' => ['PIX', 'CREDIT_CARD_EARLY_BUYER', 'BANK_SLIP'],
            'metadata'       => [
                ['key' => 'código', 'value' => 'YMC'],
            ],
        ]);

        dump($response);
        return;

        $response = $client->get('/v2/plans');

        $plans = collect($response['content'] ?? $response);

        $this->table(
            ['UUID', 'Título', 'Ativo', 'Métodos', 'Valores'],
            $plans->map(fn (array $p): array => [
                $p['uuid'],
                $p['title'],
                $p['active'] ? 'sim' : 'não',
                implode(', ', $p['acceptPaymentMethods'] ?? []),
                collect($p['values'] ?? [])->map(fn ($v) => "{$v['type']}: {$v['valuePerMonth']}")->join(' | '),
            ])
        );

        foreach ($plans as $bartePlan) {
            $plan = $this->persistPlan($bartePlan);

            collect($bartePlan['values'] ?? [])
                ->each(fn(array $value) => $plan->prices()->updateOrCreate(
                    ['provider_price_id' => "{$bartePlan['uuid']}-{$value['type']}"],
                    [
                        'billing_scheme' => 'per_unit',
                        'tiers_mode' => 'not-selected',
                        'type' => 'recurring',
                        'unit_amount_decimal' => (int)round($value['valuePerMonth'] * 100),
                        'active' => $bartePlan['active'],
                        'default' => $value['type'] === 'MONTHLY',
                        'metadata' => []
                    ]
                ));
        }

        $this->info("Planos sincronizados: {$plans->count()}");
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
                'unit_label' => 'empresa',
                'active' => $bartePlan['active'],
                'statement_descriptor' => str($bartePlan['title'])->upper()->limit(22)->toString(),
            ]
        );
    }
}
