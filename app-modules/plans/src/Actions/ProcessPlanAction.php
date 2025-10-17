<?php

namespace TresPontosTech\Plans\Actions;

use App\DTO\ProcessPlanDTO;
use Illuminate\Support\Facades\Date;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Plans\Models\Item;
use TresPontosTech\Vouchers\Enums\VoucherStatusEnum;

class ProcessPlanAction
{
    public function handle(ProcessPlanDTO $payload): void
    {
        $company = Company::query()->find($payload->companyId);

        $company->plans()->attach($payload->itemId, [
            'status' => $payload->status,
            'subscription_starting_at' => $payload->subscriptionStartingAt,
        ]);

        $item = Item::query()->find($payload->itemId);

        foreach (range(1, $item->plan->hours_included) as $ignored) {
            $company->vouchers()->create([
                'code' => Uuid::uuid4()->toString(),
                'status' => VoucherStatusEnum::Pending,
                'valid_until' => Date::parse($payload->subscriptionStartingAt)->addMonth(),
            ]);
        }
    }
}
