<?php

namespace Database\Seeders;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $this->generateUsers();

        $this->getConsultants();
        $this->generateCompanies();

        $admin = User::factory()->admin()->create();
        Company::all()->each(fn ($company) => $company->employees()->attach($admin, ['role' => CompanyRoleEnum::Owner->value]));
        Company::query()->inRandomOrder()->first()->update(['slug' => 'my-company']);
    }

    private function getConsultants(): Collection
    {
        return Consultant::all();
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
