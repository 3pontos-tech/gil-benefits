<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs;

use TresPontosTech\Api\Enums\AppAccessDenial;
use TresPontosTech\Company\Models\Company;

final readonly class AppAccess
{
    private function __construct(
        public ?Company $company,
        public ?AppAccessDenial $denial,
    ) {}

    public static function granted(Company $company): self
    {
        return new self($company, null);
    }

    public static function denied(AppAccessDenial $denial): self
    {
        return new self(null, $denial);
    }

    public function isGranted(): bool
    {
        return $this->company instanceof Company;
    }

    public function message(): string
    {
        return $this->denial?->message() ?? '';
    }
}
