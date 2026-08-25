<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Contracts;

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

/**
 * Cancelling an active subscription from our side.
 *
 * Deliberately not part of BillingContract: Virtu exposes no cancellation
 * endpoint at all (DELETE on a payment link only works while it is unpaid), so
 * forcing every driver to declare the method meant shipping one that could only
 * throw. Callers ask `instanceof` and hide the action instead — a button that
 * always fails is worse than no button.
 */
interface SupportsSubscriptionCancellation
{
    public function cancelSubscription(Company|User $billable): void;
}
