<?php

namespace TresPontosTech\Billing\Barte;

use App\Models\Users\User;
use Illuminate\Support\Facades\Log;
use TresPontosTech\App\Filament\Pages\UserBillingManagePage;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Pages\BillingManagePage;
use TresPontosTech\Company\Models\Company;

final readonly class BarteAdapter implements BillingContract
{
    public function __construct(
        private BarteClient $client
    ) {}

    public function ensureCustomerExists(Company|User $billable): void
    {
        $already = $this->findCustomer($billable);

        if ($already) {
            return;
        }

        // temos que obrigar os usuarios a preencher os details, ou quando estiver criando, melhor
        if ($billable instanceof User && blank($billable?->detail)) {
            return;
        }

        $response = $this->client->post('/v2/buyers', [
            'document' => [
                'documentNumber' => $billable instanceof Company ? $billable->tax_id : $billable->detail->tax_id,
                'documentType' => $billable instanceof Company ? 'cnpj' : 'cpf',
                'documentNation' => 'BR',
            ],
            'name' => $billable->name,
            'email' => $billable->email ?? $billable->owner->email,
        ]);

        BillingCustomer::query()->create([
            'billable_type' => $billable->getMorphClass(),
            'billable_id' => $billable->getKey(),
            'provider' => BillingProviderEnum::Barte,
            'provider_customer_id' => $response['uuid'],
        ]);
    }

    public function isSubscribed(Company|User $billable, string $planSlug): bool
    {
        if (! $this->findCustomer($billable)) {
            return false;
        }

        // stripe_price armazena o planExternalId no formato {uuid}-{CYCLE} (ex: 69ccaa5c-...-MONTHLY).
        // Buscamos o provider_product_id do plano (somente o UUID) e verificamos se stripe_price
        // começa com ele — isso cobre variações de ciclo (MONTHLY, YEARLY, SEMESTER).
        $planUuid = Plan::query()
            ->where('slug', $planSlug)
            ->value('provider_product_id');

        if (! $planUuid) {
            return false;
        }

        return Subscription::query()
            ->where('subscriptionable_type', $billable->getMorphClass())
            ->where('subscriptionable_id', $billable->getKey())
            ->where('stripe_status', 'active')
            ->where('stripe_price', 'like', $planUuid . '%')
            ->exists();
    }

    public function hasActiveSubscription(Company|User $billable): bool
    {
        $customer = $this->findCustomer($billable);

        if (! $customer) {
            return false;
        }

        return Subscription::query()
            ->where('subscriptionable_type', $billable->getMorphClass())
            ->where('subscriptionable_id', $billable->getKey())
            ->where('stripe_status', 'active')
            ->exists();
    }

    public function hasActivePlan(Company $company): bool
    {
        return $this->hasActiveSubscription($company);
    }

    public function createCheckout(Company|User $billable, CheckoutData $data): string
    {
        $customerId = $this->findCustomer($billable);

        $price = Price::query()
            ->where('provider_price_id', $data->priceId)
            ->with('plan')
            ->firstOrFail();

        $cycleType = str($data->priceId)->afterLast('-')->upper()->toString();
        $planUuid = $price->plan->provider_product_id;

        // Company: valor calculado por quantidade de seats com precificação por faixa; User: valor fixo do plano
        $valuePerMonth = $data->isMetered
            ? $this->pricePerSeat($data->quantity) * $data->quantity
            : $price->unit_amount_decimal / 100;

        $response = $this->client->post('/v2/payment-links', [
            'type' => 'SUBSCRIPTION',
            'uuidSellerClient' => $customerId,
            'scheduledDate' => now()->addDay()->toDateString(),
            'paymentMethods' => ['PIX', 'CREDIT_CARD'],
            'paymentSubscription' => [
                'idPlan' => 5810,
                'type' => 'MONTHLY',
                'valuePerMonth' => $valuePerMonth,
            ],
            'metadata' => [
                ['key' => 'billable_type', 'value' => $billable->getMorphClass()],
                ['key' => 'billable_id', 'value' => (string) $billable->getKey()],
                ['key' => 'barte_plan_uuid', 'value' => $planUuid],
                ['key' => 'barte_cycle_type', 'value' => $cycleType],
                ['key' => 'quantity', 'value' => $data->quantity],
            ],
        ]);

        Log::info('log quando criamos pagamento', $response);

        return $response['url'];
    }

    public function cancelSubscription(Company|User $billable): void
    {
        $subscription = Subscription::query()
            ->where('subscriptionable_type', $billable->getMorphClass())
            ->where('subscriptionable_id', $billable->getKey())
            ->where('stripe_status', 'active')
            ->latest()
            ->first();

        if (! $subscription) {
            return;
        }

        $this->client->delete('/v2/subscriptions/' . $subscription->stripe_id);

    }

    private function pricePerSeat(int $quantity): float
    {
        return match (true) {
            $quantity <= 15 => 44.90,
            $quantity <= 30 => 34.90,
            $quantity <= 70 => 24.90,
            default => 11.90,
        };
    }

    private function findCustomer(Company|User $billable): ?string
    {
        return BillingCustomer::getProviderCustomerId($billable, BillingProviderEnum::Barte);

    }

    public function getBillingPortalUrl(User|Company $billable, string $returnUrl, array $options = []): string
    {
        if ($billable instanceof Company) {
            return BillingManagePage::getUrl(tenant: $billable);
        }

        return UserBillingManagePage::getUrl();
    }
}
