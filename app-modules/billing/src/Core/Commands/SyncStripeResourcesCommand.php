<?php

namespace TresPontosTech\Billing\Core\Commands;

use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;
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
    public function handle()
    {
        $response = Cashier::stripe()->products->all();

        foreach ($response->data as $product) {
            Plan::query()->updateOrCreate([
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
}
