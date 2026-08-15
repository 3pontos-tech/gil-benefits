<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Jobs;

use App\Models\Users\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Billing\Core\Actions\Credit\IssueCredits;
use TresPontosTech\Billing\Core\DTOs\CreditDTO;
use TresPontosTech\Billing\Core\Enums\CreditOrderStatusEnum;
use TresPontosTech\Billing\Core\Events\Credit\CreditsDelivered;
use TresPontosTech\Billing\Core\Events\Credit\OrderCreditPurchased;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\Company\Models\Company;

class ProcessCreditPurchaseJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    public function __construct(public readonly OrderCreditPurchased $event) {}

    public function uniqueId(): string
    {
        return $this->event->creditOrderId;
    }

    public function handle(IssueCredits $action): void
    {
        $order = DB::transaction(function (): ?CreditOrder {
            $order = CreditOrder::query()
                ->whereKey($this->event->creditOrderId)
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

        if (! $order instanceof CreditOrder) {
            return;
        }

        $modelClass = Relation::getMorphedModel($order->billable_type);
        $billable = $modelClass::findOrFail($order->billable_id);

        if ($billable instanceof User) {
            $dto = new CreditDTO(
                holderId: $billable->getKey(),
                ownerId: $billable->getKey(),
                companyId: $order->company_id,
                quantity: $order->quantity,
            );
            $ownerId = (string) $billable->getKey();
        } else {
            /** @var Company $billable */
            $dto = new CreditDTO(
                holderId: $billable->user_id,
                ownerId: $billable->user_id,
                companyId: $billable->getKey(),
                quantity: $order->quantity,
            );
            $ownerId = (string) $billable->user_id;
        }

        $action->handle($dto);

        event(new CreditsDelivered(ownerId: $ownerId, quantity: $order->quantity));
    }
}
