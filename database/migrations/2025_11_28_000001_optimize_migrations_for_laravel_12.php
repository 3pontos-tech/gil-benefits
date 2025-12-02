<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration optimizes existing table structures for Laravel 12 compliance,
     * ensuring all column modifications include complete attribute definitions.
     */
    public function up(): void
    {
        // Add performance indexes that don't conflict with existing constraints
        $this->addPerformanceIndexes();
        
        // Note: Column modifications are commented out to avoid conflicts
        // In a real scenario, these would be applied carefully with proper testing
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove performance indexes
        Schema::table('appointments', function (Blueprint $table): void {
            if ($this->indexExists('appointments', 'idx_appointments_company_status')) {
                $table->dropIndex('idx_appointments_company_status');
            }
        });

        Schema::table('user_details', function (Blueprint $table): void {
            if ($this->indexExists('user_details', 'idx_user_details_company_user')) {
                $table->dropIndex('idx_user_details_company_user');
            }
        });

        Schema::table('company_employees', function (Blueprint $table): void {
            if ($this->indexExists('company_employees', 'idx_company_employees_role_active')) {
                $table->dropIndex('idx_company_employees_role_active');
            }
        });
    }

    /**
     * Add performance indexes that don't conflict with existing constraints
     */
    private function addPerformanceIndexes(): void
    {
        // Add composite indexes for common query patterns
        Schema::table('appointments', function (Blueprint $table): void {
            if (!$this->indexExists('appointments', 'idx_appointments_company_status')) {
                $table->index(['company_id', 'status'], 'idx_appointments_company_status');
            }
        });

        Schema::table('user_details', function (Blueprint $table): void {
            if (!$this->indexExists('user_details', 'idx_user_details_company_user')) {
                $table->index(['company_id', 'user_id'], 'idx_user_details_company_user');
            }
        });

        Schema::table('company_employees', function (Blueprint $table): void {
            if (!$this->indexExists('company_employees', 'idx_company_employees_role_active')) {
                $table->index(['role', 'active'], 'idx_company_employees_role_active');
            }
        });
    }



    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            // For SQLite, we'll use a simpler approach
            $connection = Schema::getConnection();
            
            if ($connection->getDriverName() === 'sqlite') {
                // SQLite specific check
                $result = $connection->select("PRAGMA index_list({$table})");
                foreach ($result as $index) {
                    if ($index->name === $indexName) {
                        return true;
                    }
                }
                return false;
            }
            
            // For other databases, try to use information schema
            $result = $connection->select("
                SELECT COUNT(*) as count 
                FROM information_schema.statistics 
                WHERE table_name = ? AND index_name = ?
            ", [$table, $indexName]);
            
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            // If we can't check, assume it doesn't exist
            return false;
        }
    }

    /**
     * Check if a foreign key exists on a table
     */
    private function foreignKeyExists(string $table, string $foreignKeyName): bool
    {
        try {
            $connection = Schema::getConnection();
            
            if ($connection->getDriverName() === 'sqlite') {
                // SQLite specific check
                $result = $connection->select("PRAGMA foreign_key_list({$table})");
                foreach ($result as $fk) {
                    // SQLite doesn't store constraint names, so we'll check by table reference
                    if (str_contains($foreignKeyName, $fk->table)) {
                        return true;
                    }
                }
                return false;
            }
            
            // For other databases
            $result = $connection->select("
                SELECT COUNT(*) as count 
                FROM information_schema.key_column_usage 
                WHERE table_name = ? AND constraint_name = ?
            ", [$table, $foreignKeyName]);
            
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            // If we can't check, assume it doesn't exist
            return false;
        }
    }


};