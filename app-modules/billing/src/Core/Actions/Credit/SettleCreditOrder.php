<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions\Credit;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Billing\Core\DTOs\CreditDTO;
use TresPontosTech\Billing\Core\Enums\CreditOrderStatusEnum;
use TresPontosTech\Billing\Core\Events\Credit\CreditsDelivered;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\Company\Models\Company;

final readonly class SettleCreditOrder
{
    public function __construct(
        private IssueCredits $issueCredits,
    ) {}

    public function handle(string $creditOrderId): void
    {
        $order = $this->claim($creditOrderId);

        if (! $order instanceof CreditOrder) {
            return;
        }

        $dto = $this->creditFor($order);

        $this->issueCredits->handle($dto);

        event(new CreditsDelivered(
            ownerId: (string) $dto->ownerId,
            quantity: $order->quantity,
        ));
    }

    private function claim(string $creditOrderId): ?CreditOrder
    {
        return DB::transaction(function () use ($creditOrderId): ?CreditOrder {
            $order = CreditOrder::query()
                ->whereKey($creditOrderId)
                ->lockForUpdate()
                ->first();

            if (! $order instanceof CreditOrder || $order->isPaid()) {
                return null;
            }

            $order->update([
                'status' => CreditOrderStatusEnum::Paid,
                'paid_at' => now(),
            ]);

            return $order;
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
            );
        }

        /** @var Company $billable */
        return new CreditDTO(
            holderId: $billable->user_id,
            ownerId: $billable->user_id,
            companyId: $billable->getKey(),
            quantity: $order->quantity,
        );
    }
}
