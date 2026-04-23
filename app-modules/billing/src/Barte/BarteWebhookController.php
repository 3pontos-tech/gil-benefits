<?php

namespace TresPontosTech\Billing\Barte;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

class BarteWebhookController extends Controller
{
    public function __construct(private readonly BarteClient $client) {}

    public function handle(Request $request): Response
    {
        $payload = $request->all();

        Log::channel('barte')->info('Webhook recebido', $payload);

        if (($payload['status'] ?? null) !== 'PAID') {
            return response('', 200);
        }

        $metadata = collect($payload['metadata'] ?? [])
            ->pluck('value', 'key');

        $planUuid     = $metadata->get('barte_plan_uuid');
        $cycleType    = $metadata->get('barte_cycle_type');
        $valuePerMonth = (float) $metadata->get('value_per_month');
        $billableType = $metadata->get('billable_type');
        $billableId   = $metadata->get('billable_id');

        if (!$planUuid || !$billableType || !$billableId) {
            return response('', 200);
        }

        $billableClass = Relation::getMorphedModel($billableType);
        $billable = $billableClass::findOrFail($billableId);

        $buyerUuid = BillingCustomer::getProviderCustomerId($billable, BillingProviderEnum::Barte);

        $subscription = $this->client->post('/v2/subscriptions', [
            'uuidPlan'   => $planUuid,
            'uuidBuyer'  => $buyerUuid,
            'startDate'  => Carbon::today()->toDateString(),
            'basicValue' => [
                'type'          => $cycleType,
                'valuePerMonth' => $valuePerMonth,
            ],
            'payment' => [
                'method'    => $payload['paymentMethod'] ?? 'PIX',
                'fraudData' => [
                    'email' => $billable->email ?? $billable->owner->email,
                    'name'  => $billable->name,
                ],
            ],
        ]);

        Subscription::query()->updateOrCreate(
            ['stripe_id' => $subscription['uuid']],
            [
                'subscriptionable_type' => $billable->getMorphClass(),
                'subscriptionable_id'   => $billable->getKey(),
                'type'                  => 'default',
                'stripe_status'         => strtolower($subscription['status']),
                'stripe_price'          => "{$planUuid}-{$cycleType}",
                'quantity'              => 1,
            ]
        );

        return response('', 200);
    }
}
