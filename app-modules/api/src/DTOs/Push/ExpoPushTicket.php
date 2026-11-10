<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs\Push;

use TresPontosTech\Api\Enums\ExpoPushError;

/**
 * A resposta imediata da Expo a uma mensagem: aceita (com o id do ticket, para buscar o recibo
 * depois) ou recusada (com o código do erro). A `message` da Expo não é guardada, porque repete
 * o token do aparelho.
 */
final readonly class ExpoPushTicket
{
    public function __construct(
        public ?string $id,
        public ?string $errorCode,
    ) {}

    /**
     * @param  array<mixed>  $ticket
     */
    public static function fromExpo(array $ticket): self
    {
        $id = $ticket['id'] ?? null;
        $errorCode = data_get($ticket, 'details.error');

        return new self(
            id: ($ticket['status'] ?? null) === 'ok' && is_string($id) ? $id : null,
            errorCode: is_string($errorCode) ? $errorCode : null,
        );
    }

    public function isAccepted(): bool
    {
        return $this->id !== null;
    }

    public function error(): ?ExpoPushError
    {
        return $this->errorCode === null ? null : ExpoPushError::tryFrom($this->errorCode);
    }
}
