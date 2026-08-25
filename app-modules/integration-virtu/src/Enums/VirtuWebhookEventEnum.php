<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Enums;

/**
 * Webhook event types Virtu emits, as sent in the `X-Webhook-Event` header and
 * the payload's `event` field.
 *
 * Note what is absent: there is no subscription lifecycle event. Barte reported
 * PENDING / ACTIVE / DEFAULTER / INACTIVE; here a recurring charge simply arrives
 * as another TRANSACTION with `data.subscriptions` populated. Detecting a lapsed
 * subscription therefore means noticing a charge that never came, not receiving
 * an event.
 */
enum VirtuWebhookEventEnum: string
{
    case Transaction = 'TRANSACTION';
    case Refund = 'REFUND';
    case RefundPartial = 'REFUND_PARTIAL';
    case Withdrawal = 'WITHDRAWAL';
}
