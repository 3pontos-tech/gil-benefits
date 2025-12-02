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

class Laravel12OptimizedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder creates optimized test data following Laravel 12 best practices
     * with proper foreign key relationships and performance considerations.
     */
    public function run(): void
    {
        // Skip in production to prevent accidental data creation
        if (app()->isProduction()) {
            $this->command->warn('Skipping Laravel 12 optimized seeder in production environment');
            return;
        }

        $this->command->info('Creating Laravel 12 optimized test data...');

        // Create test data in proper order to respect foreign key constraints
        $users = $this->createOptimizedUsers();
        $companies = $this->createOptimizedCompanies($users);
        $consultants = $this->createOptimizedConsultants();
        $this->createOptimizedAppointments($users, $companies, $consultants);
        $this->createOptimizedCompanyEmployees($users, $companies);

        $this->command->info('Laravel 12 optimized test data created successfully!');
    }

    /**
     * Create optimized users with proper attribute definitions
     */
    private function createOptimizedUsers(): array
    {
        $this->command->info('Creating optimized users...');

        $users = [];

        // Create system admin with all required attributes
        $users['system_admin'] = User::firstOrCreate(
            ['email' => 'admin@laravel12.test'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'external_id' => 'SYS_ADMIN_001',
            ]
        );

        // Create company owners with proper relationships
        for ($i = 1; $i <= 3; $i++) {
            $users["owner_{$i}"] = User::firstOrCreate(
                ['email' => "owner{$i}@laravel12.test"],
                [
                    'name' => "Company Owner {$i}",
                    'password' => Hash::make('owner123'),
                    'email_verified_at' => now(),
                    'external_id' => "OWNER_" . str_pad($i, 3, '0', STR_PAD_LEFT),
                ]
            );

            // Create user details with optimized attributes
            Detail::firstOrCreate(
                ['user_id' => $users["owner_{$i}"]->id],
                [
                    'phone_number' => "+55 11 9999-{$i}001",
                    'document_id' => "RG12345678{$i}",
                    'tax_id' => "12345678{$i}01",
                    'integration_id' => "INT_OWNER_{$i}",
                    'company_id' => null, // Will be set after company creation
                ]
            );
        }

        // Create managers with proper attributes
        for ($i = 1; $i <= 2; $i++) {
            $users["manager_{$i}"] = User::firstOrCreate(
                ['email' => "manager{$i}@laravel12.test"],
                [
                    'name' => "Manager {$i}",
                    'password' => Hash::make('manager123'),
                    'email_verified_at' => now(),
                    'external_id' => "MGR_" . str_pad($i, 3, '0', STR_PAD_LEFT),
                ]
            );

            Detail::firstOrCreate(
                ['user_id' => $users["manager_{$i}"]->id],
                [
                    'phone_number' => "+55 11 9999-{$i}002",
                    'document_id' => "RG87654321{$i}",
                    'tax_id' => "87654321{$i}01",
                    'integration_id' => "INT_MGR_{$i}",
                    'company_id' => null,
                ]
            );
        }

        // Create employees with optimized data
        for ($i = 1; $i <= 5; $i++) {
            $users["employee_{$i}"] = User::firstOrCreate(
                ['email' => "employee{$i}@laravel12.test"],
                [
                    'name' => "Employee {$i}",
                    'password' => Hash::make('employee123'),
                    'email_verified_at' => now(),
                    'external_id' => "EMP_" . str_pad($i, 3, '0', STR_PAD_LEFT),
                ]
            );

            Detail::firstOrCreate(
                ['user_id' => $users["employee_{$i}"]->id],
                [
                    'phone_number' => "+55 11 9999-{$i}003",
                    'document_id' => "RG11223344{$i}",
                    'tax_id' => "11223344{$i}01",
                    'integration_id' => "INT_EMP_{$i}",
                    'company_id' => null,
                ]
            );
        }

        return $users;
    }

    /**
     * Create optimized companies with proper foreign key relationships
     */
    private function createOptimizedCompanies(array $users): array
    {
        $this->command->info('Creating optimized companies...');

        $companies = [];

        // Create main test companies with all required attributes
        $companyData = [
            [
                'name' => 'Laravel 12 Test Company',
                'slug' => 'laravel-12-test',
                'tax_id' => '12.345.678/0001-90',
                'partner_code' => 'L12TEST001',
                'owner_key' => 'owner_1',
            ],
            [
                'name' => 'Optimized Solutions Ltd',
                'slug' => 'optimized-solutions',
                'tax_id' => '98.765.432/0001-10',
                'partner_code' => 'OPT001',
                'owner_key' => 'owner_2',
            ],
            [
                'name' => 'Performance First Inc',
                'slug' => 'performance-first',
                'tax_id' => '11.222.333/0001-44',
                'partner_code' => 'PERF001',
                'owner_key' => 'owner_3',
            ],
        ];

        foreach ($companyData as $index => $data) {
            $companies["company_{$index}"] = Company::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'tax_id' => $data['tax_id'],
                    'partner_code' => $data['partner_code'],
                    'user_id' => $users[$data['owner_key']]->id,
                    'stripe_id' => 'cus_test_' . strtolower($data['partner_code']),
                    'trial_ends_at' => now()->addDays(30),
                ]
            );

            // Update user details with company_id
            $users[$data['owner_key']]->details->update([
                'company_id' => $companies["company_{$index}"]->id,
            ]);
        }

        return $companies;
    }

    /**
     * Create optimized consultants with proper attributes
     */
    private function createOptimizedConsultants(): array
    {
        $this->command->info('Creating optimized consultants...');

        $consultants = [];

        $consultantData = [
            [
                'name' => 'Dr. Laravel Expert',
                'slug' => 'dr-laravel-expert',
                'email' => 'laravel.expert@consultants.test',
                'phone' => '+55 11 98888-0001',
                'external_id' => 'CONS_LARAVEL_001',
                'short_description' => 'Laravel 12 Optimization Specialist',
                'biography' => 'Dr. Laravel Expert specializes in Laravel 12 performance optimization and migration strategies...',
                'readme' => 'Please prepare detailed requirements before our consultation...',
            ],
            [
                'name' => 'Maria Database',
                'slug' => 'maria-database',
                'email' => 'maria.database@consultants.test',
                'phone' => '+55 11 98888-0002',
                'external_id' => 'CONS_DB_002',
                'short_description' => 'Database Performance Expert',
                'biography' => 'Maria Database is an expert in database optimization and query performance...',
                'readme' => 'Database consultations require access to query logs and performance metrics...',
            ],
            [
                'name' => 'Carlos Migration',
                'slug' => 'carlos-migration',
                'email' => 'carlos.migration@consultants.test',
                'phone' => '+55 11 98888-0003',
                'external_id' => 'CONS_MIG_003',
                'short_description' => 'Migration Specialist',
                'biography' => 'Carlos Migration specializes in Laravel version upgrades and migration optimization...',
                'readme' => 'Migration projects require thorough testing environment setup...',
            ],
        ];

        foreach ($consultantData as $index => $data) {
            $consultants[] = Consultant::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'socials_urls' => json_encode([
                        'linkedin' => "https://linkedin.com/in/{$data['slug']}",
                        'github' => "https://github.com/{$data['slug']}",
                        'twitter' => "https://twitter.com/{$data['slug']}",
                    ]),
                ])
            );
        }

        return $consultants;
    }

    /**
     * Create optimized appointments with proper relationships
     */
    private function createOptimizedAppointments(array $users, array $companies, array $consultants): void
    {
        $this->command->info('Creating optimized appointments...');

        $statuses = ['draft', 'pending', 'scheduling', 'active', 'completed', 'cancelled'];
        $categoryTypes = ['consultation', 'optimization', 'migration', 'performance-review', 'follow-up'];

        // Create appointments with proper foreign key relationships
        for ($i = 1; $i <= 30; $i++) {
            $appointmentDate = now()->addDays(rand(-15, 45))->addHours(rand(8, 18));
            $userKey = array_rand($users);
            $companyKey = array_rand($companies);
            $consultant = $consultants[array_rand($consultants)];

            Appointment::firstOrCreate(
                ['external_appointment_id' => 'APT_L12_' . str_pad($i, 6, '0', STR_PAD_LEFT)],
                [
                    'user_id' => $users[$userKey]->id,
                    'company_id' => $companies[$companyKey]->id,
                    'consultant_id' => $consultant->id,
                    'external_opportunity_id' => 'OPP_L12_' . str_pad($i, 6, '0', STR_PAD_LEFT),
                    'category_type' => $categoryTypes[array_rand($categoryTypes)],
                    'appointment_at' => $appointmentDate,
                    'status' => $statuses[array_rand($statuses)],
                ]
            );
        }

        // Create some appointments without consultants (self-service)
        for ($i = 31; $i <= 35; $i++) {
            $appointmentDate = now()->addDays(rand(1, 30))->addHours(rand(8, 18));
            $userKey = array_rand($users);
            $companyKey = array_rand($companies);

            Appointment::firstOrCreate(
                ['external_appointment_id' => 'APT_SELF_' . str_pad($i, 6, '0', STR_PAD_LEFT)],
                [
                    'user_id' => $users[$userKey]->id,
                    'company_id' => $companies[$companyKey]->id,
                    'consultant_id' => null,
                    'external_opportunity_id' => 'OPP_SELF_' . str_pad($i, 6, '0', STR_PAD_LEFT),
                    'category_type' => 'self-service',
                    'appointment_at' => $appointmentDate,
                    'status' => 'pending',
                ]
            );
        }
    }

    /**
     * Create optimized company employee relationships
     */
    private function createOptimizedCompanyEmployees(array $users, array $companies): void
    {
        $this->command->info('Creating optimized company employee relationships...');

        // Assign owners to their companies
        $companies['company_0']->employees()->syncWithoutDetaching([
            $users['owner_1']->id => [
                'role' => CompanyRoleEnum::Owner->value,
                'active' => true,
            ],
        ]);

        $companies['company_1']->employees()->syncWithoutDetaching([
            $users['owner_2']->id => [
                'role' => CompanyRoleEnum::Owner->value,
                'active' => true,
            ],
        ]);

        $companies['company_2']->employees()->syncWithoutDetaching([
            $users['owner_3']->id => [
                'role' => CompanyRoleEnum::Owner->value,
                'active' => true,
            ],
        ]);

        // Assign managers to companies
        $companies['company_0']->employees()->syncWithoutDetaching([
            $users['manager_1']->id => [
                'role' => CompanyRoleEnum::Manager->value,
                'active' => true,
            ],
        ]);

        $companies['company_1']->employees()->syncWithoutDetaching([
            $users['manager_2']->id => [
                'role' => CompanyRoleEnum::Manager->value,
                'active' => true,
            ],
        ]);

        // Assign employees to companies
        $employeeAssignments = [
            'company_0' => ['employee_1', 'employee_2'],
            'company_1' => ['employee_3', 'employee_4'],
            'company_2' => ['employee_5'],
        ];

        foreach ($employeeAssignments as $companyKey => $employeeKeys) {
            foreach ($employeeKeys as $employeeKey) {
                $companies[$companyKey]->employees()->syncWithoutDetaching([
                    $users[$employeeKey]->id => [
                        'role' => CompanyRoleEnum::Employee->value,
                        'active' => true,
                    ],
                ]);

                // Update user details with company_id
                $users[$employeeKey]->details->update([
                    'company_id' => $companies[$companyKey]->id,
                ]);
            }
        }

        // Assign system admin to all companies as owner
        foreach ($companies as $company) {
            $company->employees()->syncWithoutDetaching([
                $users['system_admin']->id => [
                    'role' => CompanyRoleEnum::Owner->value,
                    'active' => true,
                ],
            ]);
        }
    }
}