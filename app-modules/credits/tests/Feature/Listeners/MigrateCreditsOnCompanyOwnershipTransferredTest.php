<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Queue;
use TresPontosTech\Company\Actions\TransferCompanyOwnership;
use TresPontosTech\Company\Events\CompanyOwnershipTransferred;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Jobs\MigrateCompanyCreditsOwnerJob;
use TresPontosTech\Credits\Listeners\MigrateCreditsOnCompanyOwnershipTransferred;
use TresPontosTech\Credits\Models\UserCredit;

it('dispatches the migration job when the ownership transfer event is handled', function (): void {
    Queue::fake();

    $previousOwner = User::factory()->create();
    $newOwner = User::factory()->create();
    $company = Company::factory()->create(['user_id' => $newOwner->getKey()]);

    resolve(MigrateCreditsOnCompanyOwnershipTransferred::class)
        ->handle(new CompanyOwnershipTransferred($company, $previousOwner, $newOwner));

    Queue::assertPushed(MigrateCompanyCreditsOwnerJob::class, fn (MigrateCompanyCreditsOwnerJob $job): bool => $job->company->is($company)
        && $job->previousOwnerId === (string) $previousOwner->getKey());
});

it('migrates company credits to the new owner when ownership is transferred', function (): void {
    $oldOwner = User::factory()->create();
    $newOwner = User::factory()->create();
    $employee = User::factory()->create();
    $company = Company::factory()->create(['user_id' => $oldOwner->getKey()]);

    $pool = UserCredit::factory()->available()->create([
        'owner_id' => $oldOwner->getKey(),
        'holder_id' => $oldOwner->getKey(),
        'company_id' => $company->getKey(),
    ]);

    $distributed = UserCredit::factory()->available()->create([
        'owner_id' => $oldOwner->getKey(),
        'holder_id' => $employee->getKey(),
        'company_id' => $company->getKey(),
    ]);

    $selfBought = UserCredit::factory()->available()->create([
        'owner_id' => $employee->getKey(),
        'holder_id' => $employee->getKey(),
        'company_id' => $company->getKey(),
    ]);

    resolve(TransferCompanyOwnership::class)->handle($company, $newOwner);

    expect($pool->fresh()->owner_id)->toBe((string) $newOwner->getKey())
        ->and($pool->fresh()->holder_id)->toBe((string) $newOwner->getKey());

    expect($distributed->fresh()->owner_id)->toBe((string) $newOwner->getKey())
        ->and($distributed->fresh()->holder_id)->toBe((string) $employee->getKey());

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
