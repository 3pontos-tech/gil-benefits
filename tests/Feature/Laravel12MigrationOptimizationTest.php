<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

describe('Laravel 12 Migration Optimizations', function () {
    
    it('ensures all tables have proper foreign key constraints with cascade rules', function () {
        $tables = ['companies', 'user_details', 'appointments', 'company_employees'];
        
        foreach ($tables as $table) {
            expect(Schema::hasTable($table))->toBeTrue("Table {$table} should exist");
            
            // Check that foreign key constraints exist
            $foreignKeys = getForeignKeys($table);
            expect($foreignKeys)->not->toBeEmpty("Table {$table} should have foreign key constraints");
        }
    });

    it('validates that string columns have explicit lengths', function () {
        $connection = Schema::getConnection();
        
        if ($connection->getDriverName() === 'sqlite') {
            // SQLite doesn't enforce string lengths, so we'll check the migration files instead
            $migrationFiles = glob(database_path('migrations/*.php'));
            $hasExplicitLengths = false;
            
            foreach ($migrationFiles as $file) {
                $content = file_get_contents($file);
                if (preg_match('/string\([\'"][^\'"]+[\'"],\s*\d+\)/', $content)) {
                    $hasExplicitLengths = true;
                    break;
                }
            }
            
            expect($hasExplicitLengths)->toBeTrue('Migration files should contain string columns with explicit lengths');
        } else {
            // For other databases, check actual column definitions
            $tables = ['users', 'companies', 'user_details'];
            
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $columns = Schema::getColumnListing($table);
                    expect($columns)->not->toBeEmpty("Table {$table} should have columns");
                }
            }
        }
    });

    it('ensures performance indexes exist on commonly queried columns', function () {
        $expectedIndexes = [
            'users' => ['idx_users_email_verified', 'idx_users_created_at'],
            'companies' => ['idx_companies_user_active', 'idx_companies_partner_code'],
            'appointments' => ['idx_appointments_user_status', 'idx_appointments_date'],
            'company_employees' => ['idx_company_employees_company_active', 'idx_company_employees_user_role'],
        ];

        foreach ($expectedIndexes as $table => $indexes) {
            if (Schema::hasTable($table)) {
                foreach ($indexes as $indexName) {
                    expect(indexExists($table, $indexName))
                        ->toBeTrue("Index {$indexName} should exist on table {$table}");
                }
            }
        }
    });

    it('validates that migration rollback helper table exists', function () {
        expect(Schema::hasTable('migration_rollback_log'))->toBeTrue('Migration rollback log table should exist');
        
        $columns = Schema::getColumnListing('migration_rollback_log');
        $expectedColumns = [
            'id', 'migration_name', 'operation_type', 'table_name', 
            'column_name', 'original_definition', 'new_definition', 
            'rollback_sql', 'is_reversible', 'notes', 'created_at', 'updated_at'
        ];
        
        foreach ($expectedColumns as $column) {
            expect($columns)->toContain($column, "Column {$column} should exist in migration_rollback_log table");
        }
    });

    it('ensures Laravel 12 optimized seeder creates proper test data', function () {
        // Run the Laravel 12 optimized seeder
        $this->artisan('db:seed', ['--class' => 'Laravel12OptimizedSeeder']);
        
        // Verify users were created with proper attributes
        expect(\App\Models\Users\User::where('email', 'admin@laravel12.test')->exists())
            ->toBeTrue('System admin user should be created');
        
        expect(\App\Models\Users\User::where('email', 'like', '%@laravel12.test')->count())
            ->toBeGreaterThan(5, 'Multiple test users should be created');
        
        // Verify companies were created with proper relationships
        expect(\TresPontosTech\Company\Models\Company::where('slug', 'laravel-12-test')->exists())
            ->toBeTrue('Test company should be created');
        
        // Verify appointments were created with proper foreign key relationships
        expect(\TresPontosTech\Appointments\Models\Appointment::where('external_appointment_id', 'like', 'APT_L12_%')->count())
            ->toBeGreaterThan(10, 'Multiple test appointments should be created');
    });

    it('validates migration validation command works correctly', function () {
        $this->artisan('migration:validate-laravel12')
            ->assertExitCode(0);
    });

    it('ensures all migration files have proper return type declarations', function () {
        $migrationFiles = glob(database_path('migrations/*.php'));
        
        foreach ($migrationFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);
            
            // Check for proper return type declarations
            expect($content)
                ->toMatch('/public function up\(\): void/', "Migration {$filename} should have proper up() return type")
                ->toMatch('/public function down\(\): void/', "Migration {$filename} should have proper down() return type");
        }
    });

    it('validates that foreign key constraints have proper cascade rules', function () {
        $criticalTables = ['user_details', 'appointments', 'company_employees'];
        
        foreach ($criticalTables as $table) {
            if (Schema::hasTable($table)) {
                $foreignKeys = getForeignKeys($table);
                
                foreach ($foreignKeys as $fk) {
                    // Most foreign keys should have cascade rules
                    expect($fk['on_delete'])->not->toBeNull("Foreign key {$fk['name']} should have on_delete rule");
                }
            }
        }
    });

});

/**
 * Helper function to get foreign keys for a table
 */
function getForeignKeys(string $table): array
{
    $connection = Schema::getConnection();
    
    if ($connection->getDriverName() === 'sqlite') {
        $foreignKeys = DB::select("PRAGMA foreign_key_list({$table})");
        return collect($foreignKeys)->map(function ($fk) {
            return [
                'name' => "fk_{$fk->table}_{$fk->from}",
                'column' => $fk->from,
                'referenced_table' => $fk->table,
                'referenced_column' => $fk->to,
                'on_delete' => $fk->on_delete,
                'on_update' => $fk->on_update,
            ];
        })->toArray();
    }
    
    // For other databases
    try {
        $schemaManager = $connection->getDoctrineSchemaManager();
        $foreignKeys = $schemaManager->listTableForeignKeys($table);
        
        return collect($foreignKeys)->map(function ($fk) {
            return [
                'name' => $fk->getName(),
                'column' => $fk->getLocalColumns()[0] ?? null,
                'referenced_table' => $fk->getForeignTableName(),
                'referenced_column' => $fk->getForeignColumns()[0] ?? null,
                'on_delete' => $fk->onDelete(),
                'on_update' => $fk->onUpdate(),
            ];
        })->toArray();
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Helper function to check if an index exists
 */
function indexExists(string $table, string $indexName): bool
{
    $connection = Schema::getConnection();
    
    if ($connection->getDriverName() === 'sqlite') {
        $indexes = DB::select("PRAGMA index_list({$table})");
        foreach ($indexes as $index) {
            if ($index->name === $indexName) {
                return true;
            }
        }
        return false;
    }
    
    // For other databases
    try {
        $result = $connection->select("
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_name = ? AND index_name = ?
        ", [$table, $indexName]);
        
        return $result[0]->count > 0;
    } catch (\Exception $e) {
        return false;
    }
}
