<?php

namespace Database\Seeders;

use App\Models\Users\User;
use Illuminate\Database\Seeder;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;

class CompanyModuleSeeder extends Seeder
{
    /**
     * Seed the company module with test data.
     */
    public function run(): void
    {
        $this->command->info('Seeding Company module test data...');

        // Get test users created by UserModuleSeeder
        $owner = User::where('email', 'owner@test.local')->first();
        $manager = User::where('email', 'manager@test.local')->first();
        $employees = User::where('email', 'like', 'employee%@test.local')->get();

        if (!$owner || !$manager || $employees->isEmpty()) {
            $this->command->warn('Required users not found. Please run UserModuleSeeder first.');
            return;
        }

        // Create main test company
        $mainCompany = Company::factory()
            ->create([
                'name' => 'Main Test Company Ltd',
                'slug' => 'main-test-company',
                'tax_id' => '12.345.678/0001-90',
                'partner_code' => 'MAIN001',
                'user_id' => $owner->id,
            ]);

        // Attach employees with different roles
        $mainCompany->employees()->attach($owner, [
            'role' => CompanyRoleEnum::Owner->value,
            'active' => true,
        ]);

        $mainCompany->employees()->attach($manager, [
            'role' => CompanyRoleEnum::Manager->value,
            'active' => true,
        ]);

        // Attach regular employees
        foreach ($employees->take(3) as $employee) {
            $mainCompany->employees()->attach($employee, [
                'role' => CompanyRoleEnum::Employee->value,
                'active' => true,
            ]);
        }

        // Create secondary company
        $secondaryCompany = Company::factory()
            ->create([
                'name' => 'Secondary Test Company Inc',
                'slug' => 'secondary-test-company',
                'tax_id' => '98.765.432/0001-10',
                'partner_code' => 'SEC001',
                'user_id' => $owner->id,
            ]);

        $secondaryCompany->employees()->attach($owner, [
            'role' => CompanyRoleEnum::Owner->value,
            'active' => true,
        ]);

        // Attach remaining employees to secondary company
        foreach ($employees->skip(3) as $employee) {
            $secondaryCompany->employees()->attach($employee, [
                'role' => CompanyRoleEnum::Employee->value,
                'active' => true,
            ]);
        }

        // Create company with inactive employees for testing
        $testCompany = Company::factory()
            ->create([
                'name' => 'Test Scenarios Company',
                'slug' => 'test-scenarios',
                'tax_id' => '11.222.333/0001-44',
                'partner_code' => 'TEST001',
                'user_id' => $manager->id,
            ]);

        $testCompany->employees()->attach($manager, [
            'role' => CompanyRoleEnum::Owner->value,
            'active' => true,
        ]);

        // Add some inactive employee relationships
        if ($employees->count() > 0) {
            $testCompany->employees()->attach($employees->first(), [
                'role' => CompanyRoleEnum::Employee->value,
                'active' => false,
            ]);
        }

        // Create soft-deleted company for testing
        $deletedCompany = Company::factory()
            ->create([
                'name' => 'Deleted Test Company',
                'slug' => 'deleted-test-company',
                'tax_id' => '55.666.777/0001-88',
                'partner_code' => 'DEL001',
                'user_id' => $owner->id,
                'deleted_at' => now(),
            ]);

        $this->command->info('Company module test data created successfully!');
    }
};