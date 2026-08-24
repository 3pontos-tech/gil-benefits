<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Responses;

/**
 * A payment link as returned by POST /payment-links and GET /payment-links/{id}.
 *
 * One link carries two ids: `id` is `pl_…`, and the `url` embeds `checkout_…`.
 * The webhook reports the second one — confirmed against the sandbox, on the
 * first charge and on every renewal — so that is the one the adapter persists
 * and correlates on. The `pl_…` id is surfaced for diagnostics only; it never
 * appears in a webhook.
 */
readonly class PaymentLinkResponse
{
    public function __construct(
        public string $id,
        public string $url,
        public string $status,
        public int $amountCents,
        public ?string $checkoutId,
        public ?string $kind,
        public ?string $paidAt,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(array $payload): self
    {
        $url = (string) ($payload['url'] ?? '');

        return new self(
            id: (string) $payload['id'],
            url: $url,
            status: (string) ($payload['status'] ?? 'PENDING'),
            amountCents: (int) ($payload['amountCents'] ?? 0),
            checkoutId: self::checkoutIdFromUrl($url),
            kind: isset($payload['kind']) ? (string) $payload['kind'] : null,
            paidAt: isset($payload['paidAt']) ? (string) $payload['paidAt'] : null,
        );
    }

    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }

    /**
     * The checkout id is only exposed as the last segment of the hosted URL —
     * there is no field for it.
     */
    private static function checkoutIdFromUrl(string $url): ?string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        if ($path === '') {
            return null;
        }

        $segment = basename($path);

        return $segment === '' ? null : $segment;
    }
}
