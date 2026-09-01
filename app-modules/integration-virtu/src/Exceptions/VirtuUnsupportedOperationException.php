<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Exceptions;

use Exception;

/**
 * Thrown for BillingContract operations the Virtu API simply does not offer.
 *
 * Kept loud on purpose. Cancelling an active subscription is only possible from
 * the Virtu panel — DELETE /payment-links/{id} refuses a link that was already
 * paid — and a silent no-op here would tell a customer their subscription was
 * cancelled while it kept billing them.
 */
class VirtuUnsupportedOperationException extends Exception {}
