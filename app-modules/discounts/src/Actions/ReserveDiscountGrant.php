<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Actions;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use TresPontosTech\Discounts\DTOs\DiscountReservationOutcome;
use TresPontosTech\Discounts\Enums\DiscountIneligibilityReasonEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\User\Support\EmailAddress;

final readonly class ReserveDiscountGrant
{
    public function handle(
        Discount $discount,
        string $source,
        string $externalRef,
        ?string $name,
        string $email,
        string $taxId,
        ?string $phoneNumber,
        string $consentTermVersion,
        CarbonInterface $consentAcceptedAt,
    ): DiscountReservationOutcome {
        if (! $discount->active || ($discount->starts_at?->isFuture() ?? false) || ($discount->ends_at?->isPast() ?? false)) {
            return DiscountReservationOutcome::ineligible(DiscountIneligibilityReasonEnum::DiscountInactive);
        }

        $email = EmailAddress::normalize($email);

        $existing = $discount->grants()->where('external_ref', $externalRef)->first();

        if ($existing !== null) {
            return $this->outcomeForExisting($existing, $email);
        }

        if (
            User::query()->withTrashed()->where('email', $email)->exists()
            || Detail::query()->withTrashed()->whereIn('tax_id', [$taxId, $this->maskedTaxId($taxId)])->exists()
        ) {
            return DiscountReservationOutcome::ineligible(DiscountIneligibilityReasonEnum::ExistingAccount);
        }

        if ($discount->grants()->where('email', $email)->exists()) {
            return DiscountReservationOutcome::ineligible(DiscountIneligibilityReasonEnum::AlreadyGranted);
        }

        try {
            $grant = $discount->grants()->create([
                'email' => $email,
                'name' => $name,
                'source' => $source,
                'external_ref' => $externalRef,
                'tax_id' => $taxId,
                'phone_number' => $phoneNumber,
                'consent_term_version' => $consentTermVersion,
                'consent_accepted_at' => $consentAcceptedAt,
                'expires_at' => collect([now()->addDays($discount->claim_window_days), $discount->ends_at])
                    ->filter()
                    ->sortBy(fn (CarbonInterface $date): int => $date->getTimestamp())
                    ->first(),
            ]);
        } catch (UniqueConstraintViolationException) {
            $found = $discount->grants()->where('external_ref', $externalRef)->first();

            return $found?->email === $email
                ? DiscountReservationOutcome::reserved($found, false)
                : DiscountReservationOutcome::conflict();
        }

        return DiscountReservationOutcome::reserved($grant, true);
    }

    private function maskedTaxId(string $taxId): string
    {
        return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $taxId) ?? $taxId;
    }

    private function outcomeForExisting(DiscountGrant $existing, string $email): DiscountReservationOutcome
    {
        if ($existing->email !== $email) {
            return DiscountReservationOutcome::conflict();
        }

        if ($existing->revoked_at !== null) {
            return DiscountReservationOutcome::ineligible(DiscountIneligibilityReasonEnum::Revoked);
        }

        if ($existing->user_id !== null) {
            return DiscountReservationOutcome::ineligible(DiscountIneligibilityReasonEnum::AlreadyClaimed);
        }

        if ($existing->expires_at->lte(now())) {
            return DiscountReservationOutcome::ineligible(DiscountIneligibilityReasonEnum::Expired);
        }

        return DiscountReservationOutcome::reserved($existing, false);
    }
}
