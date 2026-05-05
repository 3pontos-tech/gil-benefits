<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\Enums;

enum BarteWebhookEventEnum: string
{
    // domain: SUBSCRIPTION
    case SubscriptionPending = 'PENDING';
    case SubscriptionActive = 'ACTIVE';
    case SubscriptionDefaulter = 'DEFAULTER';
    case SubscriptionInactive = 'INACTIVE';

    // domain: ORDER
    case OrderSent = 'SENT';
    case OrderPaid = 'PAID';
    case OrderPartiallyPaid = 'PARTIALLY_PAID';
    case OrderLate = 'LATE';
    case OrderAbandoned = 'ABANDONED';
    case OrderCanceled = 'CANCELED';
    case OrderRefund = 'REFUND';
    case OrderChargeback = 'CHARGEBACK';
    case OrderPreAuthorized = 'PRE_AUTHORIZED';
    case OrderDispute = 'DISPUTE';
    case OrderDisputeAlert = 'DISPUTE_ALERT';
}
