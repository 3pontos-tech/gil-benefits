<?php

namespace TresPontosTech\Billing\Barte;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
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

        $response = $this->client->post('/v2/buyers', [
            'document' => [
                'documentNumber' => $billable instanceof Company ? $billable->tax_id : $billable->detail->document_id,
                'documentType' => $billable instanceof Company ? 'cnpj' : 'cpf',
                'documentNation' => 'BR',
            ],
            'name' => $billable->name,
            'email' => $billable->email ?? $billable->slug,
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

        try {
            $document = $billable instanceof Company ? $billable->cnpj : $billable->cpf;

            $response = $this->client->get('/v2/subscriptions', [
                'customerDocument' => $document,
                'status' => 'ACTIVE',
                'size' => 50,
            ]);

            return collect($response['content'] ?? [])
                ->filter(fn (array $sub): bool => $sub['plan']['uuid'] === $planSlug
                    || $sub['plan']['title'] === $planSlug)
                ->isNotEmpty();

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

        $response = $this->client->post('/v2/payment-links', [
            'type' => 'SUBSCRIPTION',
            'uuidPlan' => $data->priceId,
            'uuidBuyer' => $customerId,
            'metadata' => collect($data->metadata)
                ->map(fn ($value, $key): array => ['key' => $key, 'value' => $value])
                ->values()
                ->all(),
        ]);

        return $response['url'];
    }

    public function getBillingPortalUrl(User|Company $billable, string $returnUrl, array $options = []): string
    {
        // Barte não tem portal gerenciado — retorna rota interna
        return route('billing.manage', ['tenant' => $billable->slug]);
    }
}
