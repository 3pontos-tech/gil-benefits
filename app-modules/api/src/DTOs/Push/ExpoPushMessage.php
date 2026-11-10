<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs\Push;

use Carbon\CarbonInterface;

/**
 * Um push como a Expo Push API o recebe, sem o destinatário: o mesmo para todos os aparelhos da
 * pessoa. É o que o canal monta e o job carrega na fila, então cada campo tem tipo, e o formato
 * da Expo fica só em `toExpoPayload()`.
 */
final readonly class ExpoPushMessage
{
    /**
     * @param  array<string, string>  $data  o que o app lê: `type`, os ids e campos do tipo (`change`)
     * @param  bool  $urgent  prioridade alta: entrega imediata mesmo com o celular em repouso
     * @param  string  $androidChannel  um dos canais que o app cria (`appointments`, `general`)
     * @param  CarbonInterface|null  $expiresAt  depois disso a Expo descarta, e o job não envia
     */
    public function __construct(
        public string $title,
        public string $body,
        public array $data,
        public bool $urgent,
        public string $androidChannel,
        public ?CarbonInterface $expiresAt = null,
    ) {}

    public function type(): ?string
    {
        return $this->data['type'] ?? null;
    }

    public function hasExpired(): bool
    {
        return $this->expiresAt?->isPast() === true;
    }

    /**
     * Uma mensagem da Expo Push API para um aparelho.
     *
     * @return array<string, mixed>
     */
    public function toExpoPayload(string $to): array
    {
        return array_filter([
            'to' => $to,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'sound' => 'default',
            'priority' => $this->urgent ? 'high' : 'default',
            'channelId' => $this->androidChannel,
            'expiration' => $this->expiresAt?->getTimestamp(),
        ], fn (mixed $value): bool => $value !== null);
    }
}
