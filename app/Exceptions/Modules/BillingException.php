<?php

declare(strict_types=1);

namespace App\Exceptions\Modules;

use App\Exceptions\BusinessLogicException;

class BillingException extends BusinessLogicException
{
    protected function getDefaultErrorCode(): string
    {
        return 'BILLING_ERROR';
    }

    public static function subscriptionNotFound(mixed $identifier, array $context = []): static
    {
        return new static(
            "Subscription not found with identifier: {$identifier}",
            404,
            null,
            array_merge($context, ['identifier' => $identifier])
        )->setErrorCode('SUBSCRIPTION_NOT_FOUND');
    }

    public static function planNotFound(string $planId, array $context = []): static
    {
        return new static(
            "Billing plan not found: {$planId}",
            404,
            null,
            array_merge($context, ['plan_id' => $planId])
        )->setErrorCode('PLAN_NOT_FOUND');
    }

    public static function paymentFailed(string $reason, array $context = []): static
    {
        return new static(
            "Payment processing failed: {$reason}",
            402,
            null,
            array_merge($context, ['reason' => $reason])
        )->setErrorCode('PAYMENT_FAILED');
    }

    public static function stripeWebhookFailed(string $eventType, string $reason, array $context = []): static
    {
        return new static(
            "Stripe webhook processing failed for {$eventType}: {$reason}",
            500,
            null,
            array_merge($context, [
                'event_type' => $eventType,
                'reason' => $reason,
            ])
        )->setErrorCode('WEBHOOK_PROCESSING_FAILED');
    }

    public static function subscriptionAlreadyActive(int $userId, array $context = []): static
    {
        return new static(
            'User already has an active subscription',
            409,
            null,
            array_merge($context, ['user_id' => $userId])
        )->setErrorCode('SUBSCRIPTION_ALREADY_ACTIVE');
    }

    public static function subscriptionCancellationFailed(string $reason, array $context = []): static
    {
        return new static(
            "Subscription cancellation failed: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason])
        )->setErrorCode('CANCELLATION_FAILED');
    }

    public static function invoiceGenerationFailed(string $reason, array $context = []): static
    {
        return new static(
            "Invoice generation failed: {$reason}",
            500,
            null,
            array_merge($context, ['reason' => $reason])
        )->setErrorCode('INVOICE_GENERATION_FAILED');
    }

    public static function refundFailed(string $reason, array $context = []): static
    {
        return new static(
            "Refund processing failed: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason])
        )->setErrorCode('REFUND_FAILED');
    }

    protected function getHttpStatusCode(): int
    {
        return match ($this->errorCode) {
            'SUBSCRIPTION_NOT_FOUND', 'PLAN_NOT_FOUND' => 404,
            'SUBSCRIPTION_ALREADY_ACTIVE' => 409,
            'PAYMENT_FAILED' => 402,
            'WEBHOOK_PROCESSING_FAILED', 'INVOICE_GENERATION_FAILED' => 500,
            default => 400,
        };
    }
}
