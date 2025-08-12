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
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $this->generateAdmin();
        $this->generateCompanyOwner();
        $this->generateCompanyEmployee();
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

    private function generateAdmin(): User
    {
        return User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
        ]);
    }

    private function generateCompanyOwner(): void
    {
        User::factory()
            ->afterCreating(
                fn($user) => $user->companies()->attach($user->ownedCompanies()->first()))
            ->has(Company::factory(), 'ownedCompanies')->create([
                'name' => 'empresa',
                'email' => 'empresa@empresa.com',
                'password' => Hash::make('password'),
            ]);
    }

    private function generateCompanyEmployee(): void
    {
        $company = Company::factory()->create();
        $employee = User::factory()->create([
            'name' => 'empregado',
            'email' => 'empregado@empregado.com',
            'password' => Hash::make('password'),
        ]);
        $company->employees()->attach($employee);
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
}
