<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\DTO;

use TresPontosTech\IntegrationChatx\Enums\ChatxEventEnum;

final readonly class ChatxResolutionAnswerDTO
{
    public function __construct(
        public ChatxEventEnum $event,
        public string $externalReference,
        public ?string $message,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $message = $payload['message'] ?? null;

        return new self(
            event: ChatxEventEnum::from((string) $payload['event']),
            externalReference: (string) $payload['external_reference'],
            message: is_string($message) && trim($message) !== '' ? trim($message) : null,
        );
    }
}
