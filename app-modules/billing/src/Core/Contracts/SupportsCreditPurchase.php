<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Contracts;

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

/**
 * One-off credit purchase through a hosted checkout.
 *
 * Separate from BillingContract because it needs something subscriptions do
 * not: the webhook has to learn which company to credit and how many credits,
 * for a purchase that leaves no subscription row behind. Barte carries that in
 * gateway metadata; a driver whose gateway has no metadata field cannot honour
 * the method, so it simply does not implement this interface.
 */
interface SupportsCreditPurchase
{
    public function purchaseCredits(Company|User $billable, Company $company, int $quantity, string $successUrl, string $cancelUrl): string;
}
