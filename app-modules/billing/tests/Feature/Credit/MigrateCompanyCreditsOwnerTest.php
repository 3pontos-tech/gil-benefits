<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;

it('migrates company credits to the new owner when the company owner changes', function (): void {
    $oldOwner = User::factory()->create();
    $newOwner = User::factory()->create();
    $employee = User::factory()->create();
    $company = Company::factory()->create(['user_id' => $oldOwner->getKey()]);

    // Pool credit still held by the previous owner.
    $pool = UserCredit::factory()->available()->create([
        'owner_id' => $oldOwner->getKey(),
        'holder_id' => $oldOwner->getKey(),
        'company_id' => $company->getKey(),
    ]);

    // Company credit already distributed to an employee.
    $distributed = UserCredit::factory()->available()->create([
        'owner_id' => $oldOwner->getKey(),
        'holder_id' => $employee->getKey(),
        'company_id' => $company->getKey(),
    ]);

    // Credit the employee bought for themselves — must stay untouched.
    $selfBought = UserCredit::factory()->available()->create([
        'owner_id' => $employee->getKey(),
        'holder_id' => $employee->getKey(),
        'company_id' => $company->getKey(),
    ]);

    $company->update(['user_id' => $newOwner->getKey()]);

    // Pool follows the ownership (owner + holder).
    expect($pool->fresh()->owner_id)->toBe((string) $newOwner->getKey())
        ->and($pool->fresh()->holder_id)->toBe((string) $newOwner->getKey());

    // Distributed credit only changes owner; the employee keeps holding it.
    expect($distributed->fresh()->owner_id)->toBe((string) $newOwner->getKey())
        ->and($distributed->fresh()->holder_id)->toBe((string) $employee->getKey());

    // Employee's own purchase is left alone.
    expect($selfBought->fresh()->owner_id)->toBe((string) $employee->getKey())
        ->and($selfBought->fresh()->holder_id)->toBe((string) $employee->getKey());
});

it('does not touch credits when a non-owner field changes', function (): void {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['user_id' => $owner->getKey()]);

    $credit = UserCredit::factory()->available()->create([
        'owner_id' => $owner->getKey(),
        'holder_id' => $owner->getKey(),
        'company_id' => $company->getKey(),
    ]);

    $company->update(['name' => 'Novo Nome']);

    expect($credit->fresh()->owner_id)->toBe((string) $owner->getKey());
});
