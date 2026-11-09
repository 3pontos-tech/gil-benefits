<?php

namespace Database\Seeders;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\Roles;

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
        Company::all()->each(fn ($company) => $company->employees()->attach($admin, ['role' => Roles::CompanyManager->value]));
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
                $company->employees()->attach($company->user_id, ['role' => Roles::CompanyOwner->value]);

                foreach ([Roles::CompanyManager, Roles::Employee] as $role) {
                    $company->employees()->attach(
                        User::factory()->hasDetail()->create(),
                        ['role' => $role->value]
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

        $company->employees()->attach($employee);

        $ownedCompany->companies()->attach($company, ['role' => Roles::CompanyOwner->value]);
    }
}
