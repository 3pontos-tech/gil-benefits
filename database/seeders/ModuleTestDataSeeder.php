<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ModuleTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds for all modules.
     * 
     * This seeder orchestrates module-specific test data creation
     * ensuring proper dependency order and data consistency.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->warn('Skipping module test data seeder in production environment');
            return;
        }

        $this->command->info('Creating module-specific test data...');

        // Seed in dependency order
        $this->call([
            UserModuleSeeder::class,
            CompanyModuleSeeder::class,
            ConsultantModuleSeeder::class,
            AppointmentModuleSeeder::class,
            BillingModuleSeeder::class,
        ]);

        $this->command->info('Module test data creation completed!');
    }
};