<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Actions;

use TresPontosTech\Discounts\Enums\DiscountGrantRevocationReasonEnum;
use TresPontosTech\Discounts\Models\DiscountGrant;

final readonly class RevokeDiscountGrant
{
    public function handle(DiscountGrant $grant, DiscountGrantRevocationReasonEnum $reason): void
    {
        if ($grant->revoked_at !== null) {
            return;
        }

        $grant->update([
            'revoked_at' => now(),
            'revoked_reason' => $reason,
        ]);
    }
}
