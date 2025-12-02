<?php

namespace Database\Seeders;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;

class ComprehensiveTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder creates comprehensive test data for all modules
     * following Laravel 12 best practices and ensuring data consistency.
     */
    public function run(): void
    {
        // Skip in production to prevent accidental data creation
        if (app()->isProduction()) {
            $this->command->warn('Skipping test data seeder in production environment');
            return;
        }

        $this->command->info('Creating comprehensive test data...');

        // Create test users with different roles
        $testUsers = $this->createTestUsers();
        
        // Create test companies with proper relationships
        $testCompanies = $this->createTestCompanies($testUsers);
        
        // Create test consultants
        $testConsultants = $this->createTestConsultants();
        
        // Create test appointments
        $this->createTestAppointments($testUsers, $testCompanies, $testConsultants);
        
        // Create test plans and plan items
        $this->createTestPlans($testCompanies);

        $this->command->info('Comprehensive test data created successfully!');
    }

    /**
     * Create test users with different roles and scenarios
     */
    private function createTestUsers(): array
    {
        $this->command->info('Creating test users...');

        $users = [];

        // Create admin user
        $users['admin'] = User::firstOrCreate(
            ['email' => 'testadmin@test.local'],
            [
                'name' => 'Test Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create company owner
        $users['owner'] = User::firstOrCreate(
            ['email' => 'testowner@test.local'],
            [
                'name' => 'Test Company Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        
        // Create detail if it doesn't exist
        if (!$users['owner']->details) {
            Detail::firstOrCreate(
                ['user_id' => $users['owner']->id],
                [
                    'phone_number' => '+55 11 99999-2001',
                    'document_id' => 'RG987654321',
                    'tax_id' => '98765432100',
                    'company_id' => 1, // Will be updated later
                ]
            );
        }

        // Create regular employees
        for ($i = 1; $i <= 3; $i++) {
            $users["employee_{$i}"] = User::factory()
                ->employee()
                ->has(Detail::factory()->state([
                    'phone_number' => "+55 11 99999-200{$i}",
                    'document_id' => "RG98765432{$i}",
                    'tax_id' => "9876543210{$i}",
                ]))
                ->create([
                    'name' => "Test Employee {$i}",
                    'email' => "testemployee{$i}@test.local",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]);
        }

        // Create manager
        $users['manager'] = User::factory()
            ->has(Detail::factory()->state([
                'phone_number' => '+55 11 99999-2004',
                'document_id' => 'RG987654324',
                'tax_id' => '98765432104',
            ]))
            ->create([
                'name' => 'Test Manager',
                'email' => 'testmanager@test.local',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

        return $users;
    }

    /**
     * Create test companies with proper relationships
     */
    private function createTestCompanies(array $users): array
    {
        $this->command->info('Creating test companies...');

        $companies = [];

        // Create main test company
        $companies['main'] = Company::factory()
            ->create([
                'name' => 'Test Company Ltd',
                'slug' => 'test-company',
                'tax_id' => '12.345.678/0001-90',
                'partner_code' => 'TEST001',
                'user_id' => $users['owner']->id,
            ]);

        // Attach employees to main company
        $companies['main']->employees()->attach($users['owner'], [
            'role' => CompanyRoleEnum::Owner->value,
            'active' => true,
        ]);

        $companies['main']->employees()->attach($users['manager'], [
            'role' => CompanyRoleEnum::Manager->value,
            'active' => true,
        ]);

        foreach (['employee_1', 'employee_2', 'employee_3'] as $employeeKey) {
            $companies['main']->employees()->attach($users[$employeeKey], [
                'role' => CompanyRoleEnum::Employee->value,
                'active' => true,
            ]);
        }

        // Create secondary test company
        $companies['secondary'] = Company::factory()
            ->create([
                'name' => 'Secondary Test Company',
                'slug' => 'secondary-test',
                'tax_id' => '98.765.432/0001-10',
                'partner_code' => 'TEST002',
                'user_id' => $users['admin']->id,
            ]);

        $companies['secondary']->employees()->attach($users['admin'], [
            'role' => CompanyRoleEnum::Owner->value,
            'active' => true,
        ]);

        // Create inactive company for testing
        $companies['inactive'] = Company::factory()
            ->create([
                'name' => 'Inactive Test Company',
                'slug' => 'inactive-test',
                'tax_id' => '11.222.333/0001-44',
                'partner_code' => 'TEST003',
                'user_id' => $users['owner']->id,
                'deleted_at' => now(),
            ]);

        return $companies;
    }

    /**
     * Create test consultants
     */
    private function createTestConsultants(): array
    {
        $this->command->info('Creating test consultants...');

        $consultants = [];

        $consultantData = [
            [
                'name' => 'Dr. John Smith',
                'slug' => 'dr-john-smith',
                'email' => 'john.smith@consultants.com',
                'phone' => '+55 11 98888-0001',
                'short_description' => 'Senior Business Consultant',
                'biography' => 'Dr. John Smith has over 15 years of experience in business consulting...',
                'readme' => 'When working with me, please schedule meetings at least 24 hours in advance...',
            ],
            [
                'name' => 'Maria Silva',
                'slug' => 'maria-silva',
                'email' => 'maria.silva@consultants.com',
                'phone' => '+55 11 98888-0002',
                'short_description' => 'HR Specialist',
                'biography' => 'Maria Silva specializes in human resources and organizational development...',
                'readme' => 'I prefer morning meetings and detailed agenda preparation...',
            ],
            [
                'name' => 'Carlos Rodriguez',
                'slug' => 'carlos-rodriguez',
                'email' => 'carlos.rodriguez@consultants.com',
                'phone' => '+55 11 98888-0003',
                'short_description' => 'Technology Consultant',
                'biography' => 'Carlos Rodriguez is an expert in digital transformation and IT strategy...',
                'readme' => 'Technical discussions require proper documentation and follow-up...',
            ],
        ];

        foreach ($consultantData as $index => $data) {
            $consultants[] = Consultant::factory()
                ->create(array_merge($data, [
                    'external_id' => "EXT_CONSULTANT_" . ($index + 1),
                    'socials_urls' => json_encode([
                        'linkedin' => "https://linkedin.com/in/{$data['slug']}",
                        'twitter' => "https://twitter.com/{$data['slug']}",
                    ]),
                ]));
        }

        return $consultants;
    }

    /**
     * Create test appointments
     */
    private function createTestAppointments(array $users, array $companies, array $consultants): void
    {
        $this->command->info('Creating test appointments...');

        $statuses = ['draft', 'pending', 'scheduling', 'active', 'completed', 'cancelled'];
        $categoryTypes = ['consultation', 'follow-up', 'strategy', 'review'];

        // Create appointments for different scenarios
        for ($i = 1; $i <= 20; $i++) {
            $appointmentDate = now()->addDays(rand(-30, 30))->addHours(rand(8, 18));
            
            Appointment::factory()
                ->create([
                    'user_id' => $users[array_rand($users)]->id,
                    'company_id' => $companies['main']->id,
                    'consultant_id' => $consultants[array_rand($consultants)]->id,
                    'external_opportunity_id' => 'OPP_' . str_pad($i, 6, '0', STR_PAD_LEFT),
                    'external_appointment_id' => 'APT_' . str_pad($i, 6, '0', STR_PAD_LEFT),
                    'category_type' => $categoryTypes[array_rand($categoryTypes)],
                    'appointment_at' => $appointmentDate,
                    'status' => $statuses[array_rand($statuses)],
                ]);
        }

        // Create some appointments without consultants (self-service)
        for ($i = 21; $i <= 25; $i++) {
            $appointmentDate = now()->addDays(rand(1, 14))->addHours(rand(8, 18));
            
            Appointment::factory()
                ->create([
                    'user_id' => $users[array_rand($users)]->id,
                    'company_id' => $companies['main']->id,
                    'consultant_id' => null,
                    'external_opportunity_id' => 'OPP_' . str_pad($i, 6, '0', STR_PAD_LEFT),
                    'external_appointment_id' => 'APT_' . str_pad($i, 6, '0', STR_PAD_LEFT),
                    'category_type' => 'self-service',
                    'appointment_at' => $appointmentDate,
                    'status' => 'pending',
                ]);
        }
    }

    /**
     * Create test plans and plan items
     */
    private function createTestPlans(array $companies): void
    {
        $this->command->info('Creating test plans...');

        // This method would create test plans if the Plan model is available
        // Since the exact Plan model structure may vary, this is a placeholder
        
        $this->command->info('Plan creation skipped - implement based on actual Plan model structure');
    }
};