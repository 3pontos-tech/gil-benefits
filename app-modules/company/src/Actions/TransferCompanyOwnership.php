<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Actions;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Enums\PreviousOwnerOutcome;
use TresPontosTech\Company\Events\CompanyOwnershipTransferred;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Tenant\Models\TenantMember;

/**
 * The only way a company changes its responsible person, so companies.user_id,
 * the owner membership and the credit pool never drift apart.
 *
 * Transferring never deletes anyone. The previous owner's membership either
 * becomes a manager membership (day-to-day access without member
 * administration) or is deactivated, which hides the company from them while
 * keeping the record. Other owner memberships are left alone.
 *
 * See company/docs/adr/2026-11-09-ownership-transfer.md.
 */
final class TransferCompanyOwnership
{
    public function handle(Company $company, User $newOwner, PreviousOwnerOutcome $previousOwnerOutcome): void
    {
        $previousOwner = DB::transaction(function () use ($company, $newOwner, $previousOwnerOutcome): ?User {
            /** @var Company $locked */
            $locked = Company::query()->lockForUpdate()->findOrFail($company->getKey());

            if ($locked->user_id === $newOwner->getKey()) {
                return null;
            }

            /** @var User $previousOwner */
            $previousOwner = $locked->owner()->withTrashed()->firstOrFail();

            $locked->forceFill(['user_id' => $newOwner->getKey()])->save();

            $previousMembership = TenantMember::query()
                ->where('company_id', $locked->getKey())
                ->where('user_id', $previousOwner->getKey())
                ->whereNull('deleted_at');

            match ($previousOwnerOutcome) {
                PreviousOwnerOutcome::KeepAsManager => $previousMembership
                    ->where('role', Roles::CompanyOwner->value)
                    ->update(['role' => Roles::CompanyManager->value]),
                PreviousOwnerOutcome::RevokeAccess => $previousMembership
                    ->update(['active' => false]),
            };

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

        $company->refresh();

        event(new CompanyOwnershipTransferred($company, $previousOwner, $newOwner, $previousOwnerOutcome));
    }
}
