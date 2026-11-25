<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationClau\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use TresPontosTech\Discounts\Actions\ReserveDiscountGrant;
use TresPontosTech\Discounts\DTOs\DiscountReservationOutcome;
use TresPontosTech\Discounts\Enums\DiscountReservationStatusEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Notifications\DiscountGrantReservedNotification;
use TresPontosTech\Discounts\Support\DiscountInviteUrl;
use TresPontosTech\Discounts\Support\TaxIdFingerprint;
use TresPontosTech\IntegrationClau\Http\Requests\StoreDiscountReservationRequest;

final class DiscountReservationController
{
    public function __invoke(StoreDiscountReservationRequest $request, ReserveDiscountGrant $reserve): JsonResponse
    {
        abort_unless(TaxIdFingerprint::isConfigured(), 503);

        $data = $request->validated();

        $discount = Discount::query()->where('slug', config('clau.discount_slug'))->first();

        if ($discount === null) {
            return $this->ineligible($data['clau_id'], 'discount_inactive');
        }

        $outcome = $reserve->handle(
            $discount,
            source: 'clau',
            externalRef: $data['clau_id'],
            name: $data['name'],
            email: $data['email'],
            taxIdFingerprint: $data['tax_id_fingerprint'],
            consentTermVersion: $data['consent']['term_version'],
            consentAcceptedAt: Date::parse($data['consent']['accepted_at']),
        );

        return match ($outcome->status) {
            DiscountReservationStatusEnum::Reserved => $this->reserved($data['clau_id'], $outcome),
            DiscountReservationStatusEnum::Ineligible => $this->ineligible($data['clau_id'], $outcome->reason->value),
            DiscountReservationStatusEnum::Conflict => $this->conflict($data['clau_id']),
        };
    }

    private function reserved(string $clauId, DiscountReservationOutcome $outcome): JsonResponse
    {
        $grant = $outcome->grant;

        if ($outcome->created) {
            Notification::route('mail', $grant->email)->notify(new DiscountGrantReservedNotification($grant));
        }

        Log::info('Reserva de desconto pedida pela Clau', ['clau_id' => $clauId, 'status' => 'reserved', 'reason' => null]);

        return response()->json([
            'status' => 'reserved',
            'reservation_id' => $grant->getKey(),
            'invite_url' => DiscountInviteUrl::for($grant),
            'expires_at' => $grant->expires_at->toIso8601String(),
        ], $outcome->created ? 201 : 200);
    }

    private function ineligible(string $clauId, string $reason): JsonResponse
    {
        Log::info('Reserva de desconto pedida pela Clau', ['clau_id' => $clauId, 'status' => 'ineligible', 'reason' => $reason]);

        return response()->json(['status' => 'ineligible', 'reason' => $reason]);
    }

    private function conflict(string $clauId): JsonResponse
    {
        Log::info('Reserva de desconto pedida pela Clau', ['clau_id' => $clauId, 'status' => 'conflict', 'reason' => null]);

        return response()->json(['status' => 'conflict'], 409);
    }
}
