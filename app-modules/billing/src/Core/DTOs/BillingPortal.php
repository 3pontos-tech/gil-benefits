<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\DTOs;

/**
 * Where a billable manages its subscription: a URL hosted by the provider, or null
 * when the provider has no portal of its own and the panel shows its billing page.
 */
final readonly class BillingPortal
{
    private function __construct(
        public ?string $url,
    ) {}

    public static function external(string $url): self
    {
        return new self($url);
    }

    public static function internal(): self
    {
        return new self(null);
    }

    public function isExternal(): bool
    {
        return $this->url !== null;
    }
}
