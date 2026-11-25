<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Contracts;

/**
 * O gateway que implementa esta interface aplica `CheckoutData::$discountPercent` ao valor
 * cobrado em todas as recorrências. Quem chama confere `instanceof` antes de oferecer desconto,
 * como em `SupportsSubscriptionCancellation`.
 */
interface SupportsCheckoutDiscount {}
