<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Enums;

/**
 * Billing cycle accepted by POST /payment-links when kind=SUBSCRIPTION.
 *
 * Careful: the API is not self-consistent. Link creation expects SEMIANNUALLY,
 * while the pre-populated checkout query string documents the same cycle as
 * SEMESTER. These values are the ones for link creation — do not reuse them to
 * build checkout URLs without checking.
 */
enum VirtuIntervalEnum: string
{
    case Monthly = 'MONTHLY';
    case Quarterly = 'QUARTERLY';
    case Semiannually = 'SEMIANNUALLY';
    case Yearly = 'YEARLY';
}
