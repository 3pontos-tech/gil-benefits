<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use TresPontosTech\Credits\DTOs\CreditDTO;
use TresPontosTech\Credits\DTOs\GrantCreditDTO;
use TresPontosTech\Credits\Exceptions\CannotGrantCreditException;
use TresPontosTech\Credits\Models\CreditGrant;

/**
 * Admin-only: gift extra consultancy credits to a company (owner pool) or to a
 * specific user (personal). Records a {@see CreditGrant} (audit) and spawns N
 * {@see UserCredit} rows. Owner and holder are the recipient — the company owner
 * for a company grant (pool), or the target user for a personal one. The granting
 * admin is tracked on the grant, not on the credit.
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

        // Owner and holder are the recipient:
        //  - company grant  → the company owner (credit sits in the company pool);
        //  - personal grant → the target user (belongs to them, usable regardless
        //    of company; excluded from the company's owned-credit views).
        // Neither is a pool→employee distribution, so transferred_at stays null.
        $recipientId = (string) ($dto->targetUser?->getKey() ?? $dto->company->user_id);

        return DB::transaction(function () use ($dto, $recipientId): CreditGrant {
            $grant = CreditGrant::query()->create([
                'admin_user_id' => $dto->adminUserId,
                'company_id' => $dto->company->getKey(),
                'target_user_id' => $dto->targetUser?->getKey(),
                'quantity' => $dto->quantity,
                'justification' => $dto->justification,
            ]);

            $this->issueCredits->handle(new CreditDTO(
                holderId: $recipientId,
                ownerId: $recipientId,
                companyId: (string) $dto->company->getKey(),
                quantity: $dto->quantity,
                grantId: (string) $grant->getKey(),
            ));

            return $grant;
        });
    }
}
