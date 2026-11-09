<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Actions;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Events\CompanyOwnershipTransferred;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Tenant\Models\TenantMember;

/**
 * The only way a company changes its responsible person. Keeps the two places
 * that say "owner" coherent: companies.user_id moves to the new owner, the new
 * owner gets an active owner membership (created or upgraded), and the previous
 * owner's membership is downgraded to manager.
 *
 * Transferring does not remove anyone. The previous owner's owner membership,
 * when there is one, becomes a manager membership: day-to-day access without
 * member administration. Taking them out of the company is a separate
 * decision, made by removing the membership afterwards.
 *
 * See company/docs/adr/2026-11-09-ownership-transfer.md.
 */
final class TransferCompanyOwnership
{
    public function handle(Company $company, User $newOwner): void
    {
        $previousOwner = DB::transaction(function () use ($company, $newOwner): ?User {
            /** @var Company $locked */
            $locked = Company::query()->lockForUpdate()->findOrFail($company->getKey());

            if ($locked->user_id === $newOwner->getKey()) {
                return null;
            }

            /** @var User $previousOwner */
            $previousOwner = $locked->owner()->withTrashed()->firstOrFail();

            $locked->forceFill(['user_id' => $newOwner->getKey()])->save();

            TenantMember::query()
                ->where('company_id', $locked->getKey())
                ->where('user_id', $previousOwner->getKey())
                ->where('role', Roles::CompanyOwner->value)
                ->whereNull('deleted_at')
                ->update(['role' => Roles::CompanyManager->value]);

            $membership = TenantMember::query()
                ->where('company_id', $locked->getKey())
                ->where('user_id', $newOwner->getKey())
                ->whereNull('deleted_at')
                ->first();

            if ($membership instanceof TenantMember) {
                $membership->forceFill(['role' => Roles::CompanyOwner, 'active' => true])->save();
            } else {
                $locked->employees()->attach($newOwner->getKey(), [
                    'role' => Roles::CompanyOwner->value,
                    'active' => true,
                ]);
            }

            return $previousOwner;
        });

        if (! $previousOwner instanceof User) {
            return;
        }

        event(new CompanyOwnershipTransferred($company->refresh(), $previousOwner, $newOwner));
    }
}
