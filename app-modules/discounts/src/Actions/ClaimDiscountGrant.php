<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Actions;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Support\TaxIdFingerprint;
use TresPontosTech\User\Support\EmailAddress;

final readonly class ClaimDiscountGrant
{
    public function handle(User $user, string $taxId): ?DiscountGrant
    {
        if (! TaxIdFingerprint::isConfigured()) {
            return null;
        }

        return DB::transaction(function () use ($user, $taxId): ?DiscountGrant {
            $grant = DiscountGrant::query()
                ->pending()
                ->where('email', EmailAddress::normalize($user->email))
                ->lockForUpdate()
                ->first();

            if ($grant === null || ! hash_equals((string) $grant->tax_id_fingerprint, TaxIdFingerprint::make($taxId))) {
                return null;
            }

            $grant->update([
                'user_id' => $user->getKey(),
                'claimed_at' => now(),
                'tax_id_fingerprint' => null,
            ]);

            return $grant;
        });
    }
}
