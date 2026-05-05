<?php

namespace TresPontosTech\Billing\Core\Commands;

use App\Models\Users\User;
use Illuminate\Console\Command;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Company\Models\Company;

class SyncBillingCustomersCommand extends Command
{
    protected $signature = 'billing:sync-customers';

    protected $description = 'Sync existing Stripe billables (Company and User) into billing_customers table';

    public function handle(): void
    {
        $this->syncBillables(
            Company::query()->whereNotNull('stripe_id')->lazyById(),
            'Companies'
        );

        $this->syncBillables(
            User::query()->whereNotNull('stripe_id')->lazyById(),
            'Users'
        );

        $this->info('Done.');
    }

    private function syncBillables(iterable $billables, string $label): void
    {
        $created = 0;
        $skipped = 0;

        $this->info(sprintf('Syncing %s...', $label));

        foreach ($billables as $billable) {
            $exists = BillingCustomer::query()
                ->where('billable_type', $billable->getMorphClass())
                ->where('billable_id', $billable->getKey())
                ->where('provider', BillingProviderEnum::Stripe)
                ->exists();

            if ($exists) {
                ++$skipped;

                continue;
            }

            BillingCustomer::query()->create([
                'billable_type' => $billable->getMorphClass(),
                'billable_id' => $billable->getKey(),
                'provider' => BillingProviderEnum::Stripe,
                'provider_customer_id' => $billable->stripe_id,
            ]);

            ++$created;
        }

        $this->line(sprintf('  %s: %d created, %d skipped.', $label, $created, $skipped));
    }
}
