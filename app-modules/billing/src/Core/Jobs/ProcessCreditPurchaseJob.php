<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Jobs;

use App\Models\Users\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TresPontosTech\Billing\Core\Actions\Credit\PurchaseCredits;
use TresPontosTech\Billing\Core\DTOs\CreditDTO;
use TresPontosTech\Billing\Core\Events\Credit\OrderCreditPurchased;
use TresPontosTech\Company\Models\Company;

class ProcessCreditPurchaseJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly OrderCreditPurchased $event) {}

    public function handle(PurchaseCredits $action): void
    {
        $modelClass = Relation::getMorphedModel($this->event->billableType);
        $billable = $modelClass::findOrFail($this->event->billableId);

        if ($billable instanceof User) {
            $dto = new CreditDTO(
                holderId: $billable->getKey(),
                ownerId: $billable->getKey(),
                companyId: $this->event->companyId,
                quantity: $this->event->quantity,
            );
        } else {
            /** @var Company $billable */
            $dto = new CreditDTO(
                holderId: $billable->user_id,
                ownerId: $billable->user_id,
                companyId: $billable->getKey(),
                quantity: $this->event->quantity,
            );
        }

        $action->handle($dto);
    }
}
