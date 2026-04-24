<?php

namespace TresPontosTech\Billing\Barte;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;

final readonly class BarteAdapter implements BillingContract
{
    public function __construct(
        private BarteClient $client
    ) {}

    public function ensureCustomerExists(Company|User $billable): void
    {
        $already = BillingCustomer::getProviderCustomerId($billable, BillingProviderEnum::Barte);

        if ($already) {
            return;
        }
        // temos que obrigar os usuarios a preencher os details, ou quando estiver criando, melhor
        if ($billable instanceof User && ! filled($billable?->detail)) {
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
        $customerId = BillingCustomer::getProviderCustomerId($billable, BillingProviderEnum::Barte);

        if (! $customerId) {
            return false;
        }

        $subscription = Subscription::query()
            ->where('subscriptionable_id', $billable->getKey())
            ->where('stripe_status', 'active')->latest()->first();

        if (! $subscription) {
            return false;
        }
        try {
            // maybe create a method for user and company like $this->document that returns the identifier

            $response = $this->client->get('/v2/subscriptions', [
                'uuid' => $subscription->stripe_id,
            ]);

            return $response['content'][0]['uuid'] === $subscription->stripe_id
                || $response['content'][0]['status'] === 'ACTIVE';

        } catch (BarteApiException $barteApiException) {
            if ($barteApiException->isNotFound()) {
                return false;
            }

            throw $barteApiException;
        }
    }

    public function hasActiveSubscription(Company|User $billable): bool
    {
        $customerId = BillingCustomer::getProviderCustomerId($billable, BillingProviderEnum::Barte);

        if (! $customerId) {
            return false;
        }

        try {
            $document = $billable instanceof Company ? $billable->tax_id : $billable->detail->document_id;

            $response = $this->client->get('/v2/subscriptions', [
                'customerDocument' => $document,
                'status' => 'ACTIVE',
                'size' => 1,
            ]);

            return ! empty($response['content']);

        } catch (BarteApiException $barteApiException) {
            if ($barteApiException->isNotFound()) {
                return false;
            }

            throw $barteApiException;
        }
    }

    public function hasActivePlan(Company $company): bool
    {
        return $this->hasActiveSubscription($company);
    }

    public function createCheckout(Company|User $billable, CheckoutData $data): string
    {
        $customerId = BillingCustomer::getProviderCustomerId($billable, BillingProviderEnum::Barte);

        $price = Price::query()
            ->where('provider_price_id', $data->priceId)
            ->with('plan')
            ->firstOrFail();

        $cycleType = str($data->priceId)->afterLast('-')->upper()->toString();
        $planUuid = $price->plan->provider_product_id;

        // Company: valor calculado por quantidade de seats; User: valor fixo do plano
        $valuePerMonth = $data->isMetered
            ? ($price->unit_amount_decimal / 100) * $data->quantity
            : $price->unit_amount_decimal / 100;

        $response = $this->client->post('/v2/payment-links', [
            'type' => 'SUBSCRIPTION',
            'uuidSellerClient' => $customerId,
            'scheduledDate' => now()->addDay()->toDateString(),
            'paymentMethods' => ['PIX'],
            'paymentSubscription' => [
                'idPlan' => $planUuid,
                'type' => 'MONTHLY',
                'valuePerMonth' => $valuePerMonth,
            ],
        ]);

        dd($response, $planUuid, $customerId, $cycleType);

        return $response['url'];
    }

    public function getBillingPortalUrl(User|Company $billable, string $returnUrl, array $options = []): string
    {
        // Barte não tem portal gerenciado — retorna rota interna
        return route('billing.manage', ['tenant' => $billable->slug]);
    }
}
