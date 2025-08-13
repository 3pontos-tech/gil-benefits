<?php

namespace Database\Seeders;

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

        $companies = $this->generateCompanies();
        $consultants = $this->generateConsultants();

        $this->generateVouchers($companies, $consultants);
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
            ->count(5)
            ->afterCreating(function (Company $company): void {
                $company->employees()->attach(User::factory()
                    ->count(3)
                    ->hasDetail()
                    ->create()
                );
            })
            ->create();
    }

    private function generateVouchers(Collection $companies, Collection $consultants): void
    {
        /** @var Company $company */
        foreach ($companies as $company) {
            Voucher::factory()
                ->forCompany($company)
                ->forUser($company->employees->random())
                ->forConsultant($consultants->random())
                ->withStatus(VoucherStatusEnum::Active)
                ->create();

            Voucher::factory()
                ->forCompany($company)
                ->forUser($company->employees->random())
                ->forConsultant($consultants->random())
                ->withStatus(VoucherStatusEnum::Used)
                ->create();

            Voucher::factory()
                ->forCompany($company)
                ->expired()
                ->create();

            Voucher::factory()
                ->forCompany($company)
                ->unused()
                ->count(3)
                ->create();
        }
    }

    private function generateUsers(): void
    {
        User::factory()->admin()->create();

        $ownedCompanie = User::factory()->companyOwner()->create();
        $employee = User::factory()->employee()->create();

        $company = Company::factory()
            ->create([
                'user_id' => $ownedCompanie->id,
            ]);

        $company->employees()->attach($employee);

        $ownedCompanie->companies()->attach($company);
    }
}
