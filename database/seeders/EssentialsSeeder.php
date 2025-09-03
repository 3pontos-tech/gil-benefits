<?php

namespace Database\Seeders;

use App\Enums\CompanyRoleEnum;
use App\Models\Companies\Company;
use App\Models\Users\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EssentialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create();

        $company = Company::factory()->create([
            'name' => '5Pontos',
            'slug' => '5pontos',
            'user_id' => $admin->id,
        ]);

        $company->employees()->attach($admin, [
            'role' => CompanyRoleEnum::Owner->value
        ]);

        $company->employees()->attach(User::factory()->adminCompanyEmployee()->create(), [
            'role' => CompanyRoleEnum::Employee->value,
        ]);
    }
}
