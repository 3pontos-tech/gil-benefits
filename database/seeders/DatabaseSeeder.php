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

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $this->generateAdmin();
        $this->generatePlans();

        $companies = $this->generateCompanies();
        $consultants = $this->generateConsultants();

        $this->generateVouchers($companies, $consultants);
    }
    private function generateConsultants(): Collection
    {
        return Consultant::factory()
            ->count(3)
            ->create();
    }
    private function generatePlans(): Collection
    {
        return Plan::factory()
            ->has(Item::factory(3))
            ->count(2)
            ->create();
    }
    private function generateCompanies(): Collection
    {
        return Company::factory()
            ->count(5)
            ->afterCreating(function (Company $company) {
                $company->employees()->attach(User::factory()
                    ->count(3)
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

    private function generateVouchers(Collection $companies, Collection $consultants)
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
