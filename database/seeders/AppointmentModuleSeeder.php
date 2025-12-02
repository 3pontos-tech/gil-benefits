<?php

namespace Database\Seeders;

use App\Models\Users\User;
use Illuminate\Database\Seeder;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;

class AppointmentModuleSeeder extends Seeder
{
    /**
     * Seed the appointment module with test data.
     */
    public function run(): void
    {
        $this->command->info('Seeding Appointment module test data...');

        // Get required data from other modules
        $users = User::whereIn('email', [
            'owner@test.local',
            'manager@test.local',
            'employee1@test.local',
            'employee2@test.local',
            'employee3@test.local',
        ])->get();

        $companies = Company::whereIn('slug', [
            'main-test-company',
            'secondary-test-company',
        ])->get();

        $consultants = Consultant::whereNotNull('id')->get();

        if ($users->isEmpty() || $companies->isEmpty() || $consultants->isEmpty()) {
            $this->command->warn('Required data not found. Please run other module seeders first.');
            return;
        }

        $mainCompany = $companies->where('slug', 'main-test-company')->first();
        $secondaryCompany = $companies->where('slug', 'secondary-test-company')->first();

        // Create appointments with different statuses and scenarios
        $this->createAppointmentScenarios($users, $mainCompany, $consultants);
        $this->createSecondaryCompanyAppointments($users, $secondaryCompany, $consultants);
        $this->createHistoricalAppointments($users, $mainCompany, $consultants);

        $this->command->info('Appointment module test data created successfully!');
    }

    /**
     * Create various appointment scenarios for testing
     */
    private function createAppointmentScenarios($users, $company, $consultants): void
    {
        $scenarios = [
            // Upcoming confirmed appointments
            [
                'status' => 'confirmed',
                'days_offset' => 3,
                'hour' => 10,
                'category_type' => 'consultation',
                'description' => 'Initial business consultation',
            ],
            [
                'status' => 'confirmed',
                'days_offset' => 7,
                'hour' => 14,
                'category_type' => 'follow-up',
                'description' => 'Follow-up meeting',
            ],
            [
                'status' => 'confirmed',
                'days_offset' => 10,
                'hour' => 9,
                'category_type' => 'strategy',
                'description' => 'Strategic planning session',
            ],

            // Pending appointments
            [
                'status' => 'pending',
                'days_offset' => 1,
                'hour' => 15,
                'category_type' => 'consultation',
                'description' => 'Pending approval consultation',
            ],
            [
                'status' => 'pending',
                'days_offset' => 5,
                'hour' => 11,
                'category_type' => 'review',
                'description' => 'Quarterly review meeting',
            ],

            // Draft appointments
            [
                'status' => 'draft',
                'days_offset' => 14,
                'hour' => 16,
                'category_type' => 'consultation',
                'description' => 'Draft consultation request',
            ],

            // Today's appointments
            [
                'status' => 'confirmed',
                'days_offset' => 0,
                'hour' => 13,
                'category_type' => 'follow-up',
                'description' => 'Today\'s follow-up meeting',
            ],
        ];

        foreach ($scenarios as $index => $scenario) {
            $appointmentDate = now()
                ->addDays($scenario['days_offset'])
                ->setHour($scenario['hour'])
                ->setMinute(0)
                ->setSecond(0);

            Appointment::factory()->create([
                'user_id' => $users->random()->id,
                'company_id' => $company->id,
                'consultant_id' => $consultants->random()->id,
                'external_opportunity_id' => 'OPP_SCENARIO_' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'external_appointment_id' => 'APT_SCENARIO_' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'category_type' => $scenario['category_type'],
                'appointment_at' => $appointmentDate,
                'status' => $scenario['status'],
            ]);
        }
    }

    /**
     * Create appointments for secondary company
     */
    private function createSecondaryCompanyAppointments($users, $company, $consultants): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $appointmentDate = now()
                ->addDays(rand(1, 30))
                ->setHour(rand(8, 17))
                ->setMinute([0, 15, 30, 45][rand(0, 3)])
                ->setSecond(0);

            Appointment::factory()->create([
                'user_id' => $users->random()->id,
                'company_id' => $company->id,
                'consultant_id' => $consultants->random()->id,
                'external_opportunity_id' => 'OPP_SEC_' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'external_appointment_id' => 'APT_SEC_' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'category_type' => ['consultation', 'follow-up', 'strategy', 'review'][rand(0, 3)],
                'appointment_at' => $appointmentDate,
                'status' => ['confirmed', 'pending', 'draft'][rand(0, 2)],
            ]);
        }
    }

    /**
     * Create historical appointments for testing
     */
    private function createHistoricalAppointments($users, $company, $consultants): void
    {
        // Completed appointments from the past
        for ($i = 1; $i <= 10; $i++) {
            $appointmentDate = now()
                ->subDays(rand(1, 90))
                ->setHour(rand(8, 17))
                ->setMinute([0, 15, 30, 45][rand(0, 3)])
                ->setSecond(0);

            Appointment::factory()->create([
                'user_id' => $users->random()->id,
                'company_id' => $company->id,
                'consultant_id' => $consultants->random()->id,
                'external_opportunity_id' => 'OPP_HIST_' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'external_appointment_id' => 'APT_HIST_' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'category_type' => ['consultation', 'follow-up', 'strategy', 'review'][rand(0, 3)],
                'appointment_at' => $appointmentDate,
                'status' => 'completed',
            ]);
        }

        // Cancelled appointments
        for ($i = 1; $i <= 3; $i++) {
            $appointmentDate = now()
                ->subDays(rand(1, 30))
                ->setHour(rand(8, 17))
                ->setMinute([0, 15, 30, 45][rand(0, 3)])
                ->setSecond(0);

            Appointment::factory()->create([
                'user_id' => $users->random()->id,
                'company_id' => $company->id,
                'consultant_id' => $consultants->random()->id,
                'external_opportunity_id' => 'OPP_CANC_' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'external_appointment_id' => 'APT_CANC_' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'category_type' => ['consultation', 'follow-up'][rand(0, 1)],
                'appointment_at' => $appointmentDate,
                'status' => 'cancelled',
            ]);
        }

        // Self-service appointments (no consultant assigned)
        for ($i = 1; $i <= 5; $i++) {
            $appointmentDate = now()
                ->addDays(rand(1, 14))
                ->setHour(rand(8, 17))
                ->setMinute([0, 15, 30, 45][rand(0, 3)])
                ->setSecond(0);

            Appointment::factory()->create([
                'user_id' => $users->random()->id,
                'company_id' => $company->id,
                'consultant_id' => null,
                'external_opportunity_id' => 'OPP_SELF_' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'external_appointment_id' => 'APT_SELF_' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'category_type' => 'self-service',
                'appointment_at' => $appointmentDate,
                'status' => 'pending',
            ]);
        }
    }
};