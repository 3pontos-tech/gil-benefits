<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\DTO;

/**
 * A ChatX intake payload, narrowed from the validated request. The boundary between
 * their vocabulary and ours: everything past this point speaks in support terms.
 */
final readonly class ChatxTicketDTO
{
    public function __construct(
        public string $externalReference,
        public string $visitorName,
        public string $visitorEmail,
        public ?string $visitorDocument,
        public ?string $visitorPhone,
        public ?string $visitorCompanyName,
        public string $category,
        public string $subject,
        public string $description,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        /** @var array<string, mixed> $visitor */
        $visitor = $payload['visitor'] ?? [];
        /** @var array<string, mixed> $ticket */
        $ticket = $payload['ticket'] ?? [];

        return new self(
            externalReference: (string) $payload['external_reference'],
            visitorName: (string) $visitor['name'],
            visitorEmail: (string) $visitor['email'],
            visitorDocument: self::optionalString($visitor['document'] ?? null),
            visitorPhone: self::optionalString($visitor['phone'] ?? null),
            visitorCompanyName: self::optionalString($visitor['company_name'] ?? null),
            category: (string) $ticket['category'],
            subject: (string) $ticket['subject'],
            description: (string) $ticket['description'],
        );
    }

    private static function optionalString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
