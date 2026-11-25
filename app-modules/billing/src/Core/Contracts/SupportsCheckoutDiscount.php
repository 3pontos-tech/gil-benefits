<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Contracts;

/**
 * O gateway que implementa esta interface aplica `CheckoutData::$discount` ao valor
 * cobrado nas recorrências do desconto. Quem chama confere `instanceof` antes de oferecer desconto,
 * como em `SupportsSubscriptionCancellation`.
 */
interface SupportsCheckoutDiscount {}
