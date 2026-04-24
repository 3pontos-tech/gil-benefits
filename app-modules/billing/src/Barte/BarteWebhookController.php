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

        Log::info('Barte webhook recebido', $payload);

        if (($payload['domain'] ?? null) !== 'SUBSCRIPTION') {
            return response('', 200);
        }

        $status = $payload['status'] ?? null;

        if (!in_array($status, ['PENDING', 'ACTIVE', 'DEFAULTER', 'INACTIVE'])) {
            return response('', 200);
        }

        $uuidBuyer = $payload['uuidBuyer'] ?? null;

        if (!$uuidBuyer) {
            Log::warning('Barte webhook sem uuidBuyer', ['uuid' => $payload['uuid'] ?? null]);
            return response('', 200);
        }

        $billingCustomer = BillingCustomer::query()
            ->where('provider', BillingProviderEnum::Barte)
            ->where('provider_customer_id', $uuidBuyer)
            ->first();

        if (!$billingCustomer) {
            Log::warning('Barte webhook: BillingCustomer não encontrado', ['uuidBuyer' => $uuidBuyer]);
            return response('', 200);
        }

        $metadata  = collect($payload['metadata'] ?? [])->pluck('value', 'key');
        $planUuid  = $metadata->get('barte_plan_uuid');
        $cycleType = $metadata->get('barte_cycle_type');

        $billableClass = Relation::getMorphedModel($billingCustomer->billable_type);
        $billable      = $billableClass::findOrFail($billingCustomer->billable_id);

        $endsAt = $status === 'INACTIVE' ? Carbon::now() : null;

        Subscription::query()->updateOrCreate(
            ['stripe_id' => $payload['uuid']],
            [
                'subscriptionable_type' => $billable->getMorphClass(),
                'subscriptionable_id'   => $billable->getKey(),
                'type'                  => 'default',
                'stripe_status'         => strtolower($status),
                'stripe_price'          => $planUuid && $cycleType ? "{$planUuid}-{$cycleType}" : null,
                'quantity'              => 1,
                'ends_at'               => $endsAt,
            ]
        );

        Log::info('Barte subscription atualizada', ['uuid' => $payload['uuid'], 'status' => $status]);

        return response('', 200);
    }
}
