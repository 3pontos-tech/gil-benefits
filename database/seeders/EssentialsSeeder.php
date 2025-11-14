<?php

namespace Database\Seeders;

use App\Models\Users\User;
use Illuminate\Database\Seeder;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;

class EssentialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'external_id' => 'IZtkYVpbP7JCgqjlzuAA',
        ]);

        $company = Company::factory()->create([
            'name' => '5Pontos',
            'slug' => '5pontos',
            'user_id' => $admin->id,
        ]);

        Appointment::factory()
            ->count(5)
            ->create([
                'user_id' => $admin->getKey(),
                'company_id' => $company->getKey(),
            ]);

        $company->employees()->attach($admin, [
            'role' => CompanyRoleEnum::Owner->value,
        ]);

        $company->employees()->attach(User::factory()->adminCompanyEmployee()->create(), [
            'role' => CompanyRoleEnum::Employee->value,
        ]);
    }
}
