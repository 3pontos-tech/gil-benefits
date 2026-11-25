<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Actions;

use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use TresPontosTech\Discounts\Enums\DiscountCustomerTypeEnum;
use TresPontosTech\Discounts\Enums\DiscountDistributionEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Models\DiscountRedemption;
use TresPontosTech\User\Support\EmailAddress;

/**
 * Transforma um código digitado em concessão na carteira da pessoa. O cupom fica travado
 * durante a conferência, então o limite total e a concessão única não podem estourar em corrida.
 */
final readonly class RedeemDiscountCode
{
    public function handle(User $user, string $code): DiscountGrant
    {
        return DB::transaction(function () use ($user, $code): DiscountGrant {
            $discount = $this->findDiscount($code);

            if (! $discount instanceof Discount || ! $this->isWithinWindow($discount)) {
                throw $this->invalid('Código inválido ou vencido.');
            }

            if ($discount->grants()->where('user_id', $user->getKey())->exists()) {
                throw $this->invalid('Você já resgatou este cupom.');
            }

            $isTotalFull = $discount->max_redemptions !== null
                && DiscountRedemption::query()
                    ->where('discount_id', $discount->getKey())
                    ->counting()
                    ->count() >= $discount->max_redemptions;

            if ($isTotalFull) {
                throw $this->invalid('Este cupom já atingiu o limite de usos.');
            }

            return $discount->grants()->create([
                'user_id' => $user->getKey(),
                'email' => EmailAddress::normalize($user->email),
                'name' => $user->name,
                'source' => 'code',
                'external_ref' => (string) $user->getKey(),
                'expires_at' => $this->expiresAt($discount),
                'claimed_at' => now(),
            ]);
        });
    }

    private function findDiscount(string $code): ?Discount
    {
        $normalizedCode = Discount::normalizeCodeValue($code);

        if ($normalizedCode === null) {
            return null;
        }

        return Discount::query()
            ->where('distribution', DiscountDistributionEnum::Code)
            ->where('code', $normalizedCode)
            ->where('customer_type', DiscountCustomerTypeEnum::Person)
            ->where('active', true)
            ->lockForUpdate()
            ->first();
    }

    private function isWithinWindow(Discount $discount): bool
    {
        return ($discount->starts_at === null || $discount->starts_at->lte(now()))
            && ($discount->ends_at === null || $discount->ends_at->gt(now()));
    }

    private function expiresAt(Discount $discount): ?Carbon
    {
        return collect([
            $discount->claim_window_days !== null ? now()->addDays($discount->claim_window_days) : null,
            $discount->ends_at,
        ])
            ->filter()
            ->sortBy(fn (Carbon $date): int => $date->getTimestamp())
            ->first();
    }

    private function invalid(string $message): ValidationException
    {
        return ValidationException::withMessages(['code' => $message]);
    }
}
