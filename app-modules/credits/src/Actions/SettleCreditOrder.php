<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Actions;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\DTOs\CreditDTO;
use TresPontosTech\Credits\Enums\CreditOrderStatusEnum;
use TresPontosTech\Credits\Events\CreditsDelivered;
use TresPontosTech\Credits\Models\CreditOrder;

final readonly class SettleCreditOrder
{
    public function __construct(
        private IssueCredits $issueCredits,
    ) {}

    public function handle(string $creditOrderId): void
    {
        $delivered = $this->settle($creditOrderId);

        if (! $delivered instanceof CreditsDelivered) {
            return;
        }

        event($delivered);
    }

    private function settle(string $creditOrderId): ?CreditsDelivered
    {
        return DB::transaction(function () use ($creditOrderId): ?CreditsDelivered {
            $order = CreditOrder::query()
                ->whereKey($creditOrderId)
                ->lockForUpdate()
                ->first();

            if (! $order instanceof CreditOrder || $order->isPaid()) {
                return null;
            }

            $dto = $this->creditFor($order);

            $this->issueCredits->handle($dto);

            $order->update([
                'status' => CreditOrderStatusEnum::Paid,
                'paid_at' => now(),
            ]);

            return new CreditsDelivered(
                ownerId: (string) $dto->ownerId,
                quantity: $order->quantity,
            );
        });
    }

    private function creditFor(CreditOrder $order): CreditDTO
    {
        $modelClass = Relation::getMorphedModel($order->billable_type);
        $billable = $modelClass::findOrFail($order->billable_id);

        if ($billable instanceof User) {
            return new CreditDTO(
                holderId: $billable->getKey(),
                ownerId: $billable->getKey(),
                companyId: $order->company_id,
                quantity: $order->quantity,
                creditOrderId: $order->getKey(),
            );
        }

        /** @var Company $billable */
        return new CreditDTO(
            holderId: $billable->user_id,
            ownerId: $billable->user_id,
            companyId: $billable->getKey(),
            quantity: $order->quantity,
            creditOrderId: $order->getKey(),
        );
    }
}
