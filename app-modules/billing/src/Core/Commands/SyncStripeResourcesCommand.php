<?php

namespace TresPontosTech\Billing\Core\Commands;

use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;
use Stripe\Price;
use Stripe\Product;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;

class SyncStripeResourcesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:sync-stripe';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $stripeClient = Cashier::stripe();
        $products = collect($stripeClient->products->all()->data)
            ->filter(fn (Product $product) => $product->active);

        foreach ($products as $product) {
            $plan = $this->persistStripePlan($product);

            $prices = $stripeClient->prices->all([
                'product' => $product->id,
            ])->data;

            collect($prices)
                ->filter(fn (Price $price) => $price->active)
                ->each(fn (Price $price) => $plan->prices()->updateOrCreate(['provider_price_id' => $price->id], [
                    'billing_scheme' => $price->billing_scheme,
                    'tiers_mode' => $price->tiers_mode ?? 'not-selected',
                    'type' => $price->type,
                    'unit_amount_decimal' => $price->unit_amount ?? 0,
                    'active' => $price->active,
                    'default' => false,
                    'metadata' => json_encode([
                        'price' => 250,
                        'features' => ['2 zap', 'treco de novo', 'bagulho'],
                    ]),
                ]));
        }
    }

    private function persistStripePlan(Product $product): Plan
    {
        return Plan::query()->updateOrCreate([
            'provider' => BillingProviderEnum::Stripe,
            'provider_product_id' => $product->id,
        ], [
            'name' => $product->name,
            'description' => $product->description ?? 'N/A',
            'trial_days' => null,
            'has_generic_trial' => false,
            'allow_promotion_codes' => false,
            'collect_tax_ids' => false,
            'slug' => str($product->name)->slug(),
            'type' => BillableTypeEnum::User,
            'unit_label' => 'seats',
            'active' => false,
            'statement_descriptor' => str($product->name)->explode(' ')->first(),
        ]);
    }
}
