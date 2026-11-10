<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs\Push;

use TresPontosTech\Api\Enums\ExpoPushError;

/**
 * O recibo de um ticket: se o FCM ou o APNs entregaram, ou por que não. É ele que diz que o
 * aparelho não existe mais.
 */
final readonly class ExpoPushReceipt
{
    public function __construct(
        public bool $delivered,
        public ?string $errorCode,
    ) {}

    /**
     * @param  array<mixed>  $receipt
     */
    public static function fromExpo(array $receipt): self
    {
        $errorCode = data_get($receipt, 'details.error');

        return new self(
            delivered: ($receipt['status'] ?? null) === 'ok',
            errorCode: is_string($errorCode) ? $errorCode : null,
        );
    }

    public function error(): ?ExpoPushError
    {
        return $this->errorCode === null ? null : ExpoPushError::tryFrom($this->errorCode);
    }
}
