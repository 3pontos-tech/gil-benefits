<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Actions;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Discounts\Models\DiscountGrant;

final readonly class ResolveApplicableDiscount
{
    public function handle(User $user, string $planSlug, PriceAudienceEnum $audience): ?DiscountGrant
    {
        if ($audience !== PriceAudienceEnum::Standalone) {
            return null;
        }

        return DiscountGrant::query()
            ->with('discount')
            ->where('user_id', $user->getKey())
            ->whereNull('revoked_at')
            ->where('expires_at', '>=', now())
            ->whereHas('discount', fn (Builder $query) => $query
                ->where('active', true)
                ->where('audience', $audience->value))
            ->where(fn (Builder $query) => $query
                ->whereNull('plan_slug')
                ->orWhere('plan_slug', $planSlug))
            ->first();
    }
}
