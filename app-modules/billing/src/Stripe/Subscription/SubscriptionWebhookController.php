<?php

namespace TresPontosTech\Billing\Stripe\Subscription;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;

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
}
