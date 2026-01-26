<?php

namespace Database\Seeders;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
        $admin = User::factory()
            ->admin()
            ->create();

        $appUser = User::factory()
            ->has(Detail::factory())
            ->create([
                'email' => 'daniel@5pontos.com',
                'name' => 'Daniel Reis (ADMIN TESTE)',
                'password' => Hash::make('admin'),
            ]);

        $company = Company::factory()->create([
            'name' => '5Pontos',
            'slug' => '5pontos',
            'user_id' => $admin->id,
        ]);

        $company->employees()->attach($appUser, ['role' => CompanyRoleEnum::Employee->value]);

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
