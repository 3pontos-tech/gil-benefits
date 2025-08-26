<?php

namespace Database\Seeders;

use App\Enums\CompanyRoleEnum;
use App\Enums\VoucherStatusEnum;
use App\Models\Companies\Company;
use App\Models\Consultant;
use App\Models\Plans\Item;
use App\Models\Plans\Plan;
use App\Models\Users\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $this->generateUsers();
        $this->generatePlans();

        $consultants = $this->generateConsultants();
        $companies = $this->generateCompanies();

        $this->generateVouchers($companies, $consultants);

        $admin = User::factory()->admin()->create();
        Company::all()->each(fn ($company) => $company->employees()->attach($admin, ['role' => CompanyRoleEnum::Owner->value]));
        Company::query()->inRandomOrder()->first()->update(['slug' => 'my-company']);
    }

    private function generateConsultants(): Collection
    {
        return Consultant::factory()
            ->count(5)
            ->create();
    }

    private function generatePlans(): void
    {
        Plan::factory()
            ->has(Item::factory(3))
            ->count(2)
            ->create();
    }

    private function generateCompanies(): Collection
    {
        return Company::factory()
            ->count(3)
            ->afterCreating(function (Company $company): void {
                $roles = [
                    ['role' => CompanyRoleEnum::Owner->value],
                    ['role' => CompanyRoleEnum::Manager->value],
                    ['role' => CompanyRoleEnum::Employee->value],
                ];
                foreach ($roles as $role) {
                    $company->employees()->attach(
                        User::factory()->hasDetail()->create(),
                        $role
                    );
                }

            })
            ->create();
    }

    private function generateVouchers(Collection $companies, Collection $consultants): void
    {
        /** @var Company $company */
        foreach ($companies as $company) {
            Voucher::factory()
                ->recycle($company)
                ->recycle($company->employees->random())
                ->recycle($consultants->random())
                ->withStatus(VoucherStatusEnum::Active)
                ->create();

            Voucher::factory()
                ->recycle($company)
                ->recycle($company->employees->random())
                ->recycle($consultants->random())
                ->withStatus(VoucherStatusEnum::Used)
                ->create();

            Voucher::factory()
                ->recycle($company)
                ->expired()
                ->create();

            Voucher::factory()
                ->recycle($company)
                ->unused()
                ->count(3)
                ->create();
        }
    }

    private function generateUsers(): void
    {
        $ownedCompany = User::factory()->companyOwner()->create();
        $employee = User::factory()->employee()->create();

        $company = Company::factory()
            ->create([
                'user_id' => $ownedCompany->id,
            ]);

        $company->employees()->attach($employee, [
            'role' => CompanyRoleEnum::Employee->value,
        ]);

        $ownedCompany->companies()->attach($company, [
            'role' => CompanyRoleEnum::Owner->value,
        ]);
    }
}
