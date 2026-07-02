<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions\Credit;

use Illuminate\Support\Facades\DB;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\CreditGrant;
use TresPontosTech\Billing\Core\Models\UserCredit;

/**
 * Admin-only: revoke a credit grant. Soft-deletes only the credits still
 * Available; credits already consumed (in_use/used) are kept intact, since a
 * booked appointment cannot be undone. The grant itself stays as the permanent
 * donation record — revocation lives per credit (each revoked credit's
 * deleted_at is the moment it was revoked), so partial revokes are represented
 * accurately and the history is never hidden.
 */
final readonly class RevokeCreditGrant
{
    public function handle(CreditGrant $grant): void
    {
        DB::transaction(function () use ($grant): void {
            UserCredit::query()
                ->where('grant_id', $grant->getKey())
                ->where('status', UserCreditStatusEnum::Available)
                ->get()
                ->each(fn (UserCredit $credit) => $credit->delete());
        });
    }
}
