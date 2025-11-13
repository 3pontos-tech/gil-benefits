<?php

namespace TresPontosTech\User\Listeners;

use App\Models\Users\User;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;
use TresPontosTech\IntegrationHighlevel\HighLevelClient;
use TresPontosTech\IntegrationHighlevel\Requests\UpsertContactDTO;

class RegisterUserIntoCrm
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly HighLevelClient $client
    ) {
        //
    }

    public function handle(WebhookHandled $event): void
    {
        $payload = $event->payload;
        if ($payload['type'] !== 'customer.subscription.created') {
            return;
        }

        Cashier::useCustomerModel(User::class);
        $user = Cashier::findBillable($payload['data']['object']['customer']);

        if (! $user) {
            return;
        }

        $company = $user->companies()->first();

        $response = $this->client->createContact(UpsertContactDTO::make(
            $company->name,
            $user->name,
            $user->email,
            $user->detail?->phone_number,
        ));

        $user->update([
            'external_id' => $response->contactId,
        ]);
    }
}
