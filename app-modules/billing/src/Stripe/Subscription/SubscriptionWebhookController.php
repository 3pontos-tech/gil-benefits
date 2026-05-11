<?php

namespace TresPontosTech\Billing\Stripe\Subscription;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Billing\Core\Actions\PurchaseCredits;
use TresPontosTech\Company\Models\Company;

class SubscriptionWebhookController extends WebhookController
{
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        Log::info($request->getContent());
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

        resolve(PurchaseCredits::class)->handle($owner, $company, $quantity);

        return $this->successMethod();
    }
}
