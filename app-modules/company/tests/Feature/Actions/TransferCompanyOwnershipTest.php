<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Event;
use TresPontosTech\Company\Actions\TransferCompanyOwnership;
use TresPontosTech\Company\Events\CompanyOwnershipTransferred;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Tenant\Models\TenantMember;

function membershipOf(Company $company, User $user): ?TenantMember
{
    return TenantMember::query()
        ->where('company_id', $company->getKey())
        ->where('user_id', $user->getKey())
        ->whereNull('deleted_at')
        ->first();
}

beforeEach(function (): void {
    $this->previousOwner = User::factory()->create();
    $this->company = Company::factory()->create(['user_id' => $this->previousOwner->getKey()]);
    $this->company->employees()->attach($this->previousOwner->getKey(), ['role' => Roles::CompanyOwner->value]);
    $this->newOwner = User::factory()->create();
});

it('moves the responsible to the new owner and gives them an active owner membership', function (): void {
    resolve(TransferCompanyOwnership::class)->handle($this->company, $this->newOwner);

    expect($this->company->fresh()->user_id)->toBe($this->newOwner->getKey());

    $membership = membershipOf($this->company, $this->newOwner);

    expect($membership)->not->toBeNull()
        ->and($membership->role)->toBe(Roles::CompanyOwner)
        ->and($membership->active)->toBeTrue();
});

it('keeps the previous owner in the company as a manager', function (): void {
    resolve(TransferCompanyOwnership::class)->handle($this->company, $this->newOwner);

    $membership = membershipOf($this->company, $this->previousOwner);

    expect($membership)->not->toBeNull()
        ->and($membership->role)->toBe(Roles::CompanyManager)
        ->and($membership->active)->toBeTrue()
        ->and($this->previousOwner->isCompanyOwner($this->company))->toBeFalse()
        ->and($this->previousOwner->isCompanyManager($this->company))->toBeTrue();
});

it('upgrades an existing membership of the new owner instead of duplicating it', function (): void {
    $this->company->employees()->attach($this->newOwner->getKey(), ['role' => Roles::Employee->value, 'active' => false]);

    resolve(TransferCompanyOwnership::class)->handle($this->company, $this->newOwner);

    $memberships = TenantMember::query()
        ->where('company_id', $this->company->getKey())
        ->where('user_id', $this->newOwner->getKey())
        ->whereNull('deleted_at')
        ->get();

    expect($memberships)->toHaveCount(1)
        ->and($memberships->first()->role)->toBe(Roles::CompanyOwner)
        ->and($memberships->first()->active)->toBeTrue();
});

it('accepts a new owner who is an active employee elsewhere', function (): void {
    $employer = Company::factory()->create();
    $employer->employees()->attach($this->newOwner->getKey(), ['role' => Roles::Employee->value]);

    resolve(TransferCompanyOwnership::class)->handle($this->company, $this->newOwner);

    expect($this->company->fresh()->user_id)->toBe($this->newOwner->getKey())
        ->and(membershipOf($employer, $this->newOwner)->role)->toBe(Roles::Employee);
});

it('leaves other owner memberships untouched', function (): void {
    $otherOwner = User::factory()->create();
    $this->company->employees()->attach($otherOwner->getKey(), ['role' => Roles::CompanyOwner->value]);

    resolve(TransferCompanyOwnership::class)->handle($this->company, $this->newOwner);

    expect(membershipOf($this->company, $otherOwner)->role)->toBe(Roles::CompanyOwner);
});

it('dispatches the transfer event with both parties', function (): void {
    Event::fake([CompanyOwnershipTransferred::class]);

    resolve(TransferCompanyOwnership::class)->handle($this->company, $this->newOwner);

    Event::assertDispatched(CompanyOwnershipTransferred::class, fn (CompanyOwnershipTransferred $event): bool => $event->company->is($this->company)
        && $event->previousOwner->is($this->previousOwner)
        && $event->newOwner->is($this->newOwner));
});

it('does nothing when the new owner is already the responsible', function (): void {
    Event::fake([CompanyOwnershipTransferred::class]);

    resolve(TransferCompanyOwnership::class)->handle($this->company, $this->previousOwner);

    Event::assertNotDispatched(CompanyOwnershipTransferred::class);
    expect(membershipOf($this->company, $this->previousOwner)->role)->toBe(Roles::CompanyOwner);
});

it('transfers away from a soft-deleted previous owner', function (): void {
    $this->previousOwner->delete();

    resolve(TransferCompanyOwnership::class)->handle($this->company->fresh(), $this->newOwner);

    expect($this->company->fresh()->user_id)->toBe($this->newOwner->getKey())
        ->and(membershipOf($this->company, $this->newOwner)->role)->toBe(Roles::CompanyOwner);
});

it('does not create a membership for a previous owner who had none', function (): void {
    TenantMember::query()->where('user_id', $this->previousOwner->getKey())->delete();

    resolve(TransferCompanyOwnership::class)->handle($this->company, $this->newOwner);

    expect(membershipOf($this->company, $this->previousOwner))->toBeNull()
        ->and($this->company->fresh()->user_id)->toBe($this->newOwner->getKey());
});
