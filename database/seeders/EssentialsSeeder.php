<?php

namespace Database\Seeders;

use App\Action\Plans\ProcessPlanAction;
use App\DTO\ProcessPlanDTO;
use App\Enums\CompanyRoleEnum;
use App\Models\Companies\Company;
use App\Models\Users\User;
use Illuminate\Database\Seeder;

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

        app(ProcessPlanAction::class)->handle(ProcessPlanDTO::make(
            companyId: $company->getKey(), data: [
                'item_id' => 1,
                'status' => 'active',
                'subscription_starting_at' => now(),
            ]));

        $company->vouchers()->first()->update([
            'user_id' => $admin->id,
        ]);

        $company->employees()->attach($admin, [
            'role' => CompanyRoleEnum::Owner->value,
        ]);

        $company->employees()->attach(User::factory()->adminCompanyEmployee()->create(), [
            'role' => CompanyRoleEnum::Employee->value,
        ]);
    }
}
