<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Actions;

use Illuminate\Support\Facades\DB;
use TresPontosTech\Discounts\Enums\DiscountGrantRevocationReasonEnum;
use TresPontosTech\Discounts\Enums\DiscountTaxIdCheckEnum;
use TresPontosTech\Discounts\Models\DiscountGrant;

final readonly class VerifyDiscountGrantTaxId
{
    public const int MAX_ATTEMPTS = 5;

    public function handle(DiscountGrant $grant, string $taxId): DiscountTaxIdCheckEnum
    {
        return DB::transaction(function () use ($grant, $taxId): DiscountTaxIdCheckEnum {
            $locked = DiscountGrant::query()->whereKey($grant->getKey())->lockForUpdate()->first();

            if ($locked === null || ! $locked->isPending()) {
                return DiscountTaxIdCheckEnum::Locked;
            }

            if (hash_equals((string) $locked->tax_id, $taxId)) {
                return DiscountTaxIdCheckEnum::Matches;
            }

            ++$locked->failed_tax_id_attempts;

            if ($locked->failed_tax_id_attempts >= self::MAX_ATTEMPTS) {
                $locked->revoked_at = now();
                $locked->revoked_reason = DiscountGrantRevocationReasonEnum::TooManyAttempts;
                $locked->save();

                return DiscountTaxIdCheckEnum::Locked;
            }

            $locked->save();

            return DiscountTaxIdCheckEnum::Mismatch;
        });
    }
}
