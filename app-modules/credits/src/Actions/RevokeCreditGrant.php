<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Actions;

use Illuminate\Support\Facades\DB;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\CreditGrant;
use TresPontosTech\Credits\Models\UserCredit;

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
