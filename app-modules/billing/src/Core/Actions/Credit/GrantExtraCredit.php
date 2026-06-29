<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions\Credit;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Throwable;
use TresPontosTech\Billing\Core\DTOs\CreditDTO;
use TresPontosTech\Billing\Core\DTOs\GrantCreditDTO;
use TresPontosTech\Billing\Core\Exceptions\CannotGrantCreditException;
use TresPontosTech\Billing\Core\Models\CreditGrant;

/**
 * Admin-only: gift extra consultancy credits to a company (owner pool) or to a
 * specific user. Records a {@see CreditGrant} (audit) and spawns N {@see UserCredit}
 * rows owned by the admin, so balance and consumption stay unified with purchases.
 */
final readonly class GrantExtraCredit
{
    public function __construct(private IssueCredits $issueCredits) {}

    /**
     * @throws Throwable
     */
    public function handle(GrantCreditDTO $dto): CreditGrant
    {
        if ($dto->quantity < 1) {
            throw CannotGrantCreditException::invalidQuantity();
        }

        if (trim($dto->justification) === '') {
            throw CannotGrantCreditException::emptyJustification();
        }

        $holderId = (string) ($dto->targetUser?->getKey() ?? $dto->company->user_id);

        return DB::transaction(function () use ($dto, $holderId): CreditGrant {
            $grant = CreditGrant::query()->create([
                'admin_user_id' => $dto->adminUserId,
                'company_id' => $dto->company->getKey(),
                'target_user_id' => $dto->targetUser?->getKey(),
                'quantity' => $dto->quantity,
                'justification' => $dto->justification,
            ]);

            $this->issueCredits->handle(new CreditDTO(
                holderId: $holderId,
                ownerId: $dto->adminUserId,
                companyId: (string) $dto->company->getKey(),
                quantity: $dto->quantity,
                grantId: (string) $grant->getKey(),
                // A directed grant is held by the user (allocated); a company
                // grant stays in the owner pool (not yet distributed).
                transferredAt: $dto->targetUser instanceof User ? now() : null,
            ));

            return $grant;
        });
    }
}
