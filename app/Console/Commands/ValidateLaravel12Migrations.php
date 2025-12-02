<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ValidateLaravel12Migrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migration:validate-laravel12 
                            {--fix : Attempt to fix common Laravel 12 migration issues}
                            {--report : Generate a detailed compliance report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate migrations for Laravel 12 compliance and best practices';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Validating Laravel 12 migration compliance...');
        
        $issues = [];
        $fixes = [];
        
        // Validate migration file structure
        $issues = array_merge($issues, $this->validateMigrationFiles());
        
        // Validate database schema compliance
        $issues = array_merge($issues, $this->validateDatabaseSchema());
        
        // Validate foreign key constraints
        $issues = array_merge($issues, $this->validateForeignKeyConstraints());
        
        // Validate indexes and performance
        $issues = array_merge($issues, $this->validateIndexes());
        
        // Validate rollback procedures
        $issues = array_merge($issues, $this->validateRollbackProcedures());
        
        if (empty($issues)) {
            $this->info('✅ All migrations are Laravel 12 compliant!');
            return self::SUCCESS;
        }
        
        $this->displayIssues($issues);
        
        if ($this->option('fix')) {
            $this->attemptFixes($issues);
        }
        
        if ($this->option('report')) {
            $this->generateReport($issues);
        }
        
        return self::FAILURE;
    }

    /**
     * Validate migration files for Laravel 12 compliance
     */
    private function validateMigrationFiles(): array
    {
        $issues = [];
        $migrationPath = database_path('migrations');
        $migrationFiles = File::glob($migrationPath . '/*.php');
        
        foreach ($migrationFiles as $file) {
            $content = File::get($file);
            $filename = basename($file);
            
            // Check for proper return type declarations
            if (!preg_match('/public function up\(\): void/', $content)) {
                $issues[] = [
                    'type' => 'migration_syntax',
                    'severity' => 'warning',
                    'file' => $filename,
                    'message' => 'up() method should have explicit void return type',
                    'fix' => 'Add ": void" to up() method signature'
                ];
            }
            
            if (!preg_match('/public function down\(\): void/', $content)) {
                $issues[] = [
                    'type' => 'migration_syntax',
                    'severity' => 'warning',
                    'file' => $filename,
                    'message' => 'down() method should have explicit void return type',
                    'fix' => 'Add ": void" to down() method signature'
                ];
            }
            
            // Check for column modifications without complete attributes
            if (preg_match('/->change\(\)/', $content) && !preg_match('/\/\*.*complete.*attributes.*\*\//', $content)) {
                $issues[] = [
                    'type' => 'column_modification',
                    'severity' => 'high',
                    'file' => $filename,
                    'message' => 'Column modifications should include all attributes explicitly',
                    'fix' => 'Ensure all column attributes are specified when using change()'
                ];
            }
            
            // Check for proper foreign key constraint naming
            if (preg_match('/->foreign\(/', $content) && !preg_match('/->name\(/', $content)) {
                $issues[] = [
                    'type' => 'foreign_key_naming',
                    'severity' => 'medium',
                    'file' => $filename,
                    'message' => 'Foreign key constraints should have explicit names',
                    'fix' => 'Add ->name() method to foreign key constraints'
                ];
            }
        }
        
        return $issues;
    }

    /**
     * Validate database schema for Laravel 12 compliance
     */
    private function validateDatabaseSchema(): array
    {
        $issues = [];
        $tables = $this->getAllTables();
        
        foreach ($tables as $table) {
            $columns = $this->getTableColumns($table);
            
            foreach ($columns as $column) {
                // Check for string columns without explicit length
                if ($column['type'] === 'varchar' && $column['length'] === null) {
                    $issues[] = [
                        'type' => 'column_definition',
                        'severity' => 'medium',
                        'table' => $table,
                        'column' => $column['name'],
                        'message' => 'String columns should have explicit length',
                        'fix' => 'Add explicit length to string column definition'
                    ];
                }
                
                // Check for nullable columns that should have default values
                if ($column['nullable'] && $column['default'] === null && in_array($column['type'], ['boolean', 'integer'])) {
                    $issues[] = [
                        'type' => 'column_default',
                        'severity' => 'low',
                        'table' => $table,
                        'column' => $column['name'],
                        'message' => 'Nullable columns should consider having default values',
                        'fix' => 'Add appropriate default value or make column non-nullable'
                    ];
                }
            }
        }
        
        return $issues;
    }

    /**
     * Validate foreign key constraints
     */
    private function validateForeignKeyConstraints(): array
    {
        $issues = [];
        $tables = $this->getAllTables();
        
        foreach ($tables as $table) {
            $foreignKeys = $this->getTableForeignKeys($table);
            
            foreach ($foreignKeys as $fk) {
                // Check for missing cascade rules
                if (!$fk['on_delete'] || $fk['on_delete'] === 'RESTRICT') {
                    $issues[] = [
                        'type' => 'foreign_key_cascade',
                        'severity' => 'medium',
                        'table' => $table,
                        'constraint' => $fk['name'],
                        'message' => 'Foreign key should have explicit cascade rule',
                        'fix' => 'Add onDelete() cascade rule to foreign key'
                    ];
                }
                
                if (!$fk['on_update'] || $fk['on_update'] === 'RESTRICT') {
                    $issues[] = [
                        'type' => 'foreign_key_cascade',
                        'severity' => 'low',
                        'table' => $table,
                        'constraint' => $fk['name'],
                        'message' => 'Foreign key should have explicit update cascade rule',
                        'fix' => 'Add onUpdate() cascade rule to foreign key'
                    ];
                }
            }
        }
        
        return $issues;
    }

    /**
     * Validate indexes for performance
     */
    private function validateIndexes(): array
    {
        $issues = [];
        $tables = $this->getAllTables();
        
        foreach ($tables as $table) {
            $columns = $this->getTableColumns($table);
            $indexes = $this->getTableIndexes($table);
            
            // Check for foreign key columns without indexes
            foreach ($columns as $column) {
                if (str_ends_with($column['name'], '_id') && !$this->hasIndexOnColumn($indexes, $column['name'])) {
                    $issues[] = [
                        'type' => 'missing_index',
                        'severity' => 'medium',
                        'table' => $table,
                        'column' => $column['name'],
                        'message' => 'Foreign key column should have an index',
                        'fix' => 'Add index to foreign key column'
                    ];
                }
            }
            
            // Check for commonly queried columns without indexes
            $commonQueryColumns = ['status', 'type', 'category', 'active', 'deleted_at'];
            foreach ($columns as $column) {
                if (in_array($column['name'], $commonQueryColumns) && !$this->hasIndexOnColumn($indexes, $column['name'])) {
                    $issues[] = [
                        'type' => 'performance_index',
                        'severity' => 'low',
                        'table' => $table,
                        'column' => $column['name'],
                        'message' => 'Commonly queried column should have an index',
                        'fix' => 'Add index to improve query performance'
                    ];
                }
            }
        }
        
        return $issues;
    }

    /**
     * Validate rollback procedures
     */
    private function validateRollbackProcedures(): array
    {
        $issues = [];
        $migrationPath = database_path('migrations');
        $migrationFiles = File::glob($migrationPath . '/*.php');
        
        foreach ($migrationFiles as $file) {
            $content = File::get($file);
            $filename = basename($file);
            
            // Check if down() method is empty or just has comments
            if (preg_match('/public function down\(\): void\s*\{[^}]*\}/', $content, $matches)) {
                $downMethod = $matches[0];
                $downContent = preg_replace('/\/\/.*|\/\*.*?\*\//s', '', $downMethod);
                
                if (preg_match('/\{\s*\}/', $downContent)) {
                    $issues[] = [
                        'type' => 'rollback_procedure',
                        'severity' => 'high',
                        'file' => $filename,
                        'message' => 'Migration has empty down() method - rollback not possible',
                        'fix' => 'Implement proper rollback logic in down() method'
                    ];
                }
            }
        }
        
        return $issues;
    }

    /**
     * Display validation issues
     */
    private function displayIssues(array $issues): void
    {
        $this->error('❌ Found ' . count($issues) . ' Laravel 12 compliance issues:');
        $this->newLine();
        
        $groupedIssues = collect($issues)->groupBy('severity');
        
        foreach (['high', 'medium', 'low', 'warning'] as $severity) {
            if (!$groupedIssues->has($severity)) {
                continue;
            }
            
            $color = match($severity) {
                'high' => 'red',
                'medium' => 'yellow',
                'low' => 'blue',
                'warning' => 'cyan',
            };
            
            $this->line("<fg={$color}>" . strtoupper($severity) . " SEVERITY ISSUES:</>");
            
            foreach ($groupedIssues[$severity] as $issue) {
                $location = isset($issue['file']) ? $issue['file'] : 
                           (isset($issue['table']) ? "Table: {$issue['table']}" : 'Unknown');
                
                $this->line("  • {$issue['message']}");
                $this->line("    Location: {$location}");
                $this->line("    Fix: {$issue['fix']}");
                $this->newLine();
            }
        }
    }

    /**
     * Attempt to fix common issues
     */
    private function attemptFixes(array $issues): void
    {
        $this->info('🔧 Attempting to fix common issues...');
        
        $fixableIssues = collect($issues)->where('type', 'migration_syntax');
        
        foreach ($fixableIssues as $issue) {
            if ($this->confirm("Fix return type declaration in {$issue['file']}?")) {
                $this->fixReturnTypeDeclaration($issue['file']);
            }
        }
        
        $this->info('✅ Automated fixes completed. Please review changes manually.');
    }

    /**
     * Fix return type declarations in migration files
     */
    private function fixReturnTypeDeclaration(string $filename): void
    {
        $filePath = database_path('migrations/' . $filename);
        $content = File::get($filePath);
        
        // Fix up() method
        $content = preg_replace(
            '/public function up\(\)/',
            'public function up(): void',
            $content
        );
        
        // Fix down() method
        $content = preg_replace(
            '/public function down\(\)/',
            'public function down(): void',
            $content
        );
        
        File::put($filePath, $content);
        $this->info("✅ Fixed return type declarations in {$filename}");
    }

    /**
     * Generate compliance report
     */
    private function generateReport(array $issues): void
    {
        $reportPath = storage_path('logs/laravel12-migration-report.json');
        
        $report = [
            'generated_at' => now()->toISOString(),
            'total_issues' => count($issues),
            'issues_by_severity' => collect($issues)->countBy('severity'),
            'issues_by_type' => collect($issues)->countBy('type'),
            'issues' => $issues,
        ];
        
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        $this->info("📊 Detailed report saved to: {$reportPath}");
    }

    /**
     * Get all table names
     */
    private function getAllTables(): array
    {
        $connection = Schema::getConnection();
        
        if ($connection->getDriverName() === 'sqlite') {
            return collect(DB::select("SELECT name FROM sqlite_master WHERE type='table'"))
                ->pluck('name')
                ->filter(fn($name) => !in_array($name, ['sqlite_sequence', 'migrations']))
                ->values()
                ->toArray();
        }
        
        return Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
    }

    /**
     * Get table columns information
     */
    private function getTableColumns(string $table): array
    {
        $connection = Schema::getConnection();
        
        if ($connection->getDriverName() === 'sqlite') {
            $columns = DB::select("PRAGMA table_info({$table})");
            return collect($columns)->map(function ($column) {
                return [
                    'name' => $column->name,
                    'type' => $column->type,
                    'nullable' => !$column->notnull,
                    'default' => $column->dflt_value,
                    'length' => null, // SQLite doesn't store length info
                ];
            })->toArray();
        }
        
        // For other databases, use Doctrine
        $schemaManager = $connection->getDoctrineSchemaManager();
        $columns = $schemaManager->listTableColumns($table);
        
        return collect($columns)->map(function ($column) {
            return [
                'name' => $column->getName(),
                'type' => $column->getType()->getName(),
                'nullable' => !$column->getNotnull(),
                'default' => $column->getDefault(),
                'length' => $column->getLength(),
            ];
        })->toArray();
    }

    /**
     * Get table foreign keys
     */
    private function getTableForeignKeys(string $table): array
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
     * Get table indexes
     */
    private function getTableIndexes(string $table): array
    {
        $connection = Schema::getConnection();
        
        if ($connection->getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list({$table})");
            return collect($indexes)->map(function ($index) use ($table) {
                $indexInfo = DB::select("PRAGMA index_info({$index->name})");
                return [
                    'name' => $index->name,
                    'columns' => collect($indexInfo)->pluck('name')->toArray(),
                    'unique' => $index->unique,
                ];
            })->toArray();
        }
        
        // For other databases
        try {
            $schemaManager = $connection->getDoctrineSchemaManager();
            $indexes = $schemaManager->listTableIndexes($table);
            
            return collect($indexes)->map(function ($index) {
                return [
                    'name' => $index->getName(),
                    'columns' => $index->getColumns(),
                    'unique' => $index->isUnique(),
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if table has index on specific column
     */
    private function hasIndexOnColumn(array $indexes, string $column): bool
    {
        foreach ($indexes as $index) {
            if (in_array($column, $index['columns'])) {
                return true;
            }
        }
        return false;
    }
}
