<?php

namespace Database\Seeders;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserModuleSeeder extends Seeder
{
    /**
     * Seed the user module with test data.
     */
    public function run(): void
    {
        $this->command->info('Seeding User module test data...');

        // Create roles if they don't exist
        $this->createRoles();

        // Create test users with different scenarios
        $this->createTestUsers();

        $this->command->info('User module test data created successfully!');
    }

    /**
     * Create necessary roles for testing
     */
    private function createRoles(): void
    {
        $roles = ['admin', 'company_owner', 'manager', 'employee', 'consultant'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }
    }

    /**
     * Create test users with various scenarios
     */
    private function createTestUsers(): void
    {
        // Super admin user
        $admin = User::factory()
            ->create([
                'name' => 'Super Admin',
                'email' => 'superadmin@test.local',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]);
        $admin->assignRole('admin');

        // Company owner with complete profile
        $owner = User::factory()
            ->has(Detail::factory()->state([
                'phone_number' => '+55 11 99999-1001',
                'document_id' => 'RG987654321',
                'tax_id' => '98765432100',
            ]))
            ->create([
                'name' => 'Company Owner Test',
                'email' => 'owner@test.local',
                'password' => Hash::make('owner123'),
                'email_verified_at' => now(),
            ]);
        $owner->assignRole('company_owner');

        // Manager user
        $manager = User::factory()
            ->has(Detail::factory()->state([
                'phone_number' => '+55 11 99999-1002',
                'document_id' => 'RG987654322',
                'tax_id' => '98765432101',
            ]))
            ->create([
                'name' => 'Manager Test',
                'email' => 'manager@test.local',
                'password' => Hash::make('manager123'),
                'email_verified_at' => now(),
            ]);
        $manager->assignRole('manager');

        // Regular employees
        for ($i = 1; $i <= 5; $i++) {
            $employee = User::factory()
                ->has(Detail::factory()->state([
                    'phone_number' => "+55 11 99999-10{$i}0",
                    'document_id' => "RG98765432{$i}",
                    'tax_id' => "9876543210{$i}",
                ]))
                ->create([
                    'name' => "Employee Test {$i}",
                    'email' => "employee{$i}@test.local",
                    'password' => Hash::make('employee123'),
                    'email_verified_at' => now(),
                ]);
            $employee->assignRole('employee');
        }

        // Unverified user for testing email verification
        User::factory()
            ->create([
                'name' => 'Unverified User',
                'email' => 'unverified@test.local',
                'password' => Hash::make('unverified123'),
                'email_verified_at' => null,
            ]);

        // Inactive user for testing soft deletes
        $inactiveUser = User::factory()
            ->has(Detail::factory())
            ->create([
                'name' => 'Inactive User',
                'email' => 'inactive@test.local',
                'password' => Hash::make('inactive123'),
                'email_verified_at' => now(),
                'deleted_at' => now(),
            ]);
        $inactiveUser->assignRole('employee');
    }
};