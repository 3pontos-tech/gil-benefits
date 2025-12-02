<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates a helper table to track migration rollback procedures
     * and ensure proper schema change management for Laravel 12 compliance.
     */
    public function up(): void
    {
        Schema::create('migration_rollback_log', function (Blueprint $table): void {
            $table->id();
            $table->string('migration_name', 255)->index();
            $table->string('operation_type', 100)->index(); // 'column_change', 'index_add', 'foreign_key_add', etc.
            $table->string('table_name', 255)->index();
            $table->string('column_name', 255)->nullable()->index();
            $table->json('original_definition')->nullable(); // Store original column/constraint definition
            $table->json('new_definition')->nullable(); // Store new column/constraint definition
            $table->text('rollback_sql')->nullable(); // SQL needed for rollback
            $table->boolean('is_reversible')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Composite indexes for common queries
            $table->index(['migration_name', 'table_name'], 'idx_rollback_migration_table');
            $table->index(['operation_type', 'is_reversible'], 'idx_rollback_operation_reversible');
        });

        // Log this migration's creation
        $this->logMigrationOperation(
            'create_migration_rollback_helper',
            'table_create',
            'migration_rollback_log',
            null,
            null,
            ['table_name' => 'migration_rollback_log'],
            'DROP TABLE IF EXISTS migration_rollback_log',
            true,
            'Helper table for tracking migration rollback procedures'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('migration_rollback_log');
    }

    /**
     * Log a migration operation for rollback tracking
     */
    private function logMigrationOperation(
        string $migrationName,
        string $operationType,
        string $tableName,
        ?string $columnName = null,
        ?array $originalDefinition = null,
        ?array $newDefinition = null,
        ?string $rollbackSql = null,
        bool $isReversible = true,
        ?string $notes = null
    ): void {
        // Only log if the table exists (avoid issues during initial migration)
        if (Schema::hasTable('migration_rollback_log')) {
            \DB::table('migration_rollback_log')->insert([
                'migration_name' => $migrationName,
                'operation_type' => $operationType,
                'table_name' => $tableName,
                'column_name' => $columnName,
                'original_definition' => $originalDefinition ? json_encode($originalDefinition) : null,
                'new_definition' => $newDefinition ? json_encode($newDefinition) : null,
                'rollback_sql' => $rollbackSql,
                'is_reversible' => $isReversible,
                'notes' => $notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};