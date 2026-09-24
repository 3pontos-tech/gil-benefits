<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Actions;

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

class AllocateCreditToEmployee
{
    /**
     * Transfer N available credits from the owner pool to a specific employee.
     */
    public function handle(Company $company, User $employee, int $quantity): void
    {
        UserCredit::query()
            ->where('company_id', $company->getKey())
            ->where('holder_id', $company->user_id)
            ->where('status', UserCreditStatusEnum::Available)
            ->limit($quantity)
            ->get()
            ->each(fn (UserCredit $credit) => $credit->update([
                'holder_id' => $employee->getKey(),
                'transferred_at' => now(),
            ]));
    }

    /**
     * Distribute credits equally among all active employees.
     * Requires that available credits >= active employee count.
     */
    public function handleEqually(Company $company): void
    {
        $employees = $company->employees()->wherePivot('active', true)->where('users.id', '!=', $company->user_id)->get();

        $available = UserCredit::query()
            ->where('company_id', $company->getKey())
            ->where('holder_id', $company->user_id)
            ->where('status', UserCreditStatusEnum::Available)
            ->get();

        $employeeCount = $employees->count();

        if ($employeeCount === 0 || $available->count() < $employeeCount) {
            return;
        }

        $creditsPerEmployee = (int) floor($available->count() / $employeeCount);

        $offset = 0;
        foreach ($employees as $employee) {
            $available->slice($offset, $creditsPerEmployee)->each(
                fn (UserCredit $credit) => $credit->update([
                    'holder_id' => $employee->getKey(),
                    'transferred_at' => now(),
                ])
            );
            $offset += $creditsPerEmployee;
        }
    }
}
