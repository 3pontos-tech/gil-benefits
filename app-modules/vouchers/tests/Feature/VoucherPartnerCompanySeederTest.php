<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\CompanyPlanKindEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Vouchers\Database\Seeders\VoucherPartnerCompanySeeder;
use TresPontosTech\Vouchers\Models\VoucherBatch;

use function Pest\Laravel\seed;

it('creates the partner with an owner and a live voucher program, and no batch', function (): void {
    User::factory()->create();

    seed(VoucherPartnerCompanySeeder::class);

    $company = Company::query()->where('slug', VoucherPartnerCompanySeeder::COMPANY_SLUG)->sole();
    $owner = $company->owner;

    expect($owner?->email)->toBe(VoucherPartnerCompanySeeder::OWNER_EMAIL)
        ->and($owner->hasRole(Roles::CompanyOwner->value))->toBeTrue()
        ->and($company->employees()->wherePivot('role', Roles::CompanyOwner->value)->whereKey($owner->getKey())->exists())->toBeTrue()
        ->and(strlen($company->tax_id))->toBe(14)
        ->and($company->hasActivePlan())->toBeTrue()
        ->and($company->activeContractualPlan()?->kind)->toBe(CompanyPlanKindEnum::CreditsOnly)
        ->and($company->activeContractualPlan()?->ends_at)->not->toBeNull()
        ->and(VoucherBatch::query()->where('company_id', $company->getKey())->exists())->toBeFalse();
});

it('can run twice without duplicating anything', function (): void {
    User::factory()->create();

    seed(VoucherPartnerCompanySeeder::class);
    seed(VoucherPartnerCompanySeeder::class);

    expect(Company::query()->where('slug', VoucherPartnerCompanySeeder::COMPANY_SLUG)->count())->toBe(1)
        ->and(User::query()->where('email', VoucherPartnerCompanySeeder::OWNER_EMAIL)->count())->toBe(1)
        ->and(CompanyPlan::query()->whereHas('company', fn ($q) => $q->where('slug', VoucherPartnerCompanySeeder::COMPANY_SLUG))->count())->toBe(1);
});
