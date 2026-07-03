<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Responses;

readonly class CreateItemResponse
{
    public function __construct(
        public string $itemId,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(array $payload): self
    {
        return new self(
            itemId: (string) $payload['data']['create_item']['id'],
        );
    }
}
