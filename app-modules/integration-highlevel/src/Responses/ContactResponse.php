<?php

namespace TresPontosTech\IntegrationHighlevel\Responses;

readonly class ContactResponse
{
    public function __construct(
        public string $contactId,
        public bool $isNewContact
    ) {}

    /**
     * @param  array<string, mixed>  $json
     */
    public static function make(array $json): self
    {
        return new self(
            contactId: $json['contact']['id'],
            isNewContact: $json['new']
        );
    }
}
