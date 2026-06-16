<?php

namespace TresPontosTech\Billing\Stripe\Subscription;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Billing\Core\Actions\Credit\PurchaseCredits;
use TresPontosTech\Billing\Core\DTOs\CreditDTO;
use TresPontosTech\Company\Models\Company;

class SubscriptionWebhookController extends WebhookController
{
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        $objectPayload = $payload['data']['object'];
        if (array_key_exists('metadata', $objectPayload)) {
            $metadata = $objectPayload['metadata'];

            if (array_key_exists('model', $metadata)) {
                $model = $metadata['model'];
                Cashier::useCustomerModel(Relation::getMorphedModel($model));
            }
        }

        return parent::handleWebhook($request);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'];
        $metadata = $session['metadata'] ?? [];

        if (($metadata['type'] ?? '') !== 'credits') {
            return $this->successMethod();
        }

        if (($session['payment_status'] ?? '') !== 'paid') {
            return $this->successMethod();
        }

        $companyId = $metadata['company_id'] ?? null;
        $ownerId = $metadata['owner_id'] ?? null;
        $quantity = (int) ($metadata['quantity'] ?? 0);

        if (! $companyId || $quantity <= 0) {
            return $this->successMethod();
        }

        $company = Company::query()->find($companyId);

        if (! $company) {
            return $this->successMethod();
        }

        $owner = $ownerId
            ? User::query()->find($ownerId)
            : $company->owner;

        if (! $owner) {
            return $this->successMethod();
        }

        resolve(PurchaseCredits::class)->handle(new CreditDTO(
            holderId: $owner->getKey(),
            ownerId: $owner->getKey(),
            companyId: $company->getKey(),
            quantity: $quantity,
        ));

        return $this->successMethod();
    }
}
