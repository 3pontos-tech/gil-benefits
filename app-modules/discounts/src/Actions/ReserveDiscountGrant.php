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
        string $taxIdFingerprint,
        string $consentTermVersion,
        CarbonInterface $consentAcceptedAt,
    ): DiscountReservationOutcome {
        if (! $discount->active) {
            return DiscountReservationOutcome::ineligible(DiscountIneligibilityReasonEnum::DiscountInactive);
        }

        $email = EmailAddress::normalize($email);

        $existing = $discount->grants()->where('external_ref', $externalRef)->first();

        if ($existing !== null) {
            return $this->outcomeForExisting($existing, $email);
        }

        if (
            User::query()->withTrashed()->where('email', $email)->exists()
            || Detail::query()->withTrashed()->where('tax_id_fingerprint', $taxIdFingerprint)->exists()
        ) {
            return DiscountReservationOutcome::ineligible(DiscountIneligibilityReasonEnum::ExistingAccount);
        }

        $alreadyGranted = $discount->grants()
            ->where(fn ($query) => $query->where('email', $email)->orWhere('tax_id_fingerprint', $taxIdFingerprint))
            ->exists();

        if ($alreadyGranted) {
            return DiscountReservationOutcome::ineligible(DiscountIneligibilityReasonEnum::AlreadyGranted);
        }

        try {
            $grant = $discount->grants()->create([
                'email' => $email,
                'name' => $name,
                'source' => $source,
                'external_ref' => $externalRef,
                'tax_id_fingerprint' => $taxIdFingerprint,
                'consent_term_version' => $consentTermVersion,
                'consent_accepted_at' => $consentAcceptedAt,
                'expires_at' => now()->addDays($discount->claim_window_days),
            ]);
        } catch (UniqueConstraintViolationException) {
            $found = $discount->grants()->where('external_ref', $externalRef)->first();

            return $found?->email === $email
                ? DiscountReservationOutcome::reserved($found, false)
                : DiscountReservationOutcome::conflict();
        }

        return DiscountReservationOutcome::reserved($grant, true);
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
