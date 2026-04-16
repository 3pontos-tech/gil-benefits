<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Company\Models\Company;

class SyncSubscriptionToFlammaCompany extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-subscription-to-flamma-company';

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
        $flammaCompany = Company::query()->where('slug', 'flamma-company')->first();

        if (! $flammaCompany) {
            $this->error('flamma does not exists!');
        }

        $flammaCompany?->subscriptions()->create([
            'stripe_id' => Uuid::uuid4()->toString(),
            'type' => 'company',
            'stripe_status' => 'active',
            'quantity' => 100000,
        ]);

        $this->info('successfully synced to flamma-company');
    }
}
