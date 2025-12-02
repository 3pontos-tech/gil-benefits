<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptimizeDatabaseCommand extends Command
{
    protected $signature = 'db:optimize 
                           {--analyze : Run ANALYZE on all tables}
                           {--vacuum : Run VACUUM on SQLite database}
                           {--check-indexes : Check for missing indexes}
                           {--stats : Show database statistics}';

    protected $description = 'Optimize database performance and analyze table statistics';

    public function handle(): int
    {
        $this->info('Starting database optimization...');

        if ($this->option('analyze')) {
            $this->analyzeTables();
        }

        if ($this->option('vacuum')) {
            $this->vacuumDatabase();
        }

        if ($this->option('check-indexes')) {
            $this->checkIndexes();
        }

        if ($this->option('stats')) {
            $this->showDatabaseStats();
        }

        // If no specific options, run all optimizations
        if (! $this->hasAnyOption()) {
            $this->analyzeTables();
            $this->vacuumDatabase();
            $this->checkIndexes();
            $this->showDatabaseStats();
        }

        $this->info('Database optimization completed!');

        return Command::SUCCESS;
    }

    private function hasAnyOption(): bool
    {
        return $this->option('analyze') ||
               $this->option('vacuum') ||
               $this->option('check-indexes') ||
               $this->option('stats');
    }

    private function analyzeTables(): void
    {
        $this->info('Analyzing tables...');

        $tables = $this->getAllTables();

        foreach ($tables as $table) {
            try {
                // For SQLite, we can use ANALYZE
                DB::statement("ANALYZE {$table}");
                $this->line("  ✓ Analyzed table: {$table}");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to analyze table {$table}: " . $e->getMessage());
            }
        }
    }

    private function vacuumDatabase(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->info('Running VACUUM on SQLite database...');
            try {
                DB::statement('VACUUM');
                $this->line('  ✓ VACUUM completed successfully');
            } catch (\Exception $e) {
                $this->error('  ✗ VACUUM failed: ' . $e->getMessage());
            }
        } else {
            $this->warn('VACUUM is only supported for SQLite databases');
        }
    }

    private function checkIndexes(): void
    {
        $this->info('Checking for potential missing indexes...');

        $recommendations = [
            'users' => [
                'email' => 'Frequently used for authentication',
                'created_at' => 'Used for sorting and filtering',
                'deleted_at' => 'Used with soft deletes',
            ],
            'appointments' => [
                'user_id' => 'Foreign key relationship',
                'consultant_id' => 'Foreign key relationship',
                'status' => 'Frequently filtered',
                'appointment_at' => 'Used for date filtering and sorting',
            ],
            'companies' => [
                'user_id' => 'Foreign key relationship',
                'partner_code' => 'Unique identifier for partners',
                'slug' => 'Used for routing',
            ],
        ];

        foreach ($recommendations as $table => $columns) {
            if (Schema::hasTable($table)) {
                $this->line("Table: {$table}");

                foreach ($columns as $column => $reason) {
                    $hasIndex = $this->hasIndex($table, $column);
                    $status = $hasIndex ? '✓' : '⚠';
                    $this->line("  {$status} {$column} - {$reason}");

                    if (! $hasIndex) {
                        $this->warn("    Consider adding index: CREATE INDEX idx_{$table}_{$column} ON {$table}({$column});");
                    }
                }
            }
        }
    }

    private function hasIndex(string $table, string $column): bool
    {
        try {
            $indexes = DB::select("PRAGMA index_list({$table})");

            foreach ($indexes as $index) {
                $indexInfo = DB::select("PRAGMA index_info({$index->name})");
                foreach ($indexInfo as $info) {
                    if ($info->name === $column) {
                        return true;
                    }
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function showDatabaseStats(): void
    {
        $this->info('Database Statistics:');

        try {
            // Database size
            $dbPath = database_path('database.sqlite');
            if (file_exists($dbPath)) {
                $size = filesize($dbPath);
                $sizeFormatted = $this->formatBytes($size);
                $this->line("  Database size: {$sizeFormatted}");
            }

            // Table statistics
            $tables = $this->getAllTables();
            $this->line('  Total tables: ' . count($tables));

            // Record counts for main tables
            $mainTables = ['users', 'companies', 'appointments', 'consultants'];
            foreach ($mainTables as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    $this->line("  {$table}: {$count} records");
                }
            }

            // Index statistics
            $totalIndexes = 0;
            foreach ($tables as $table) {
                try {
                    $indexes = DB::select("PRAGMA index_list({$table})");
                    $totalIndexes += count($indexes);
                } catch (\Exception $e) {
                    // Skip if unable to get index info
                }
            }
            $this->line("  Total indexes: {$totalIndexes}");

        } catch (\Exception $e) {
            $this->error('Failed to retrieve database statistics: ' . $e->getMessage());
        }
    }

    private function getAllTables(): array
    {
        try {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

            return array_map(fn ($table) => $table->name, $tables);
        } catch (\Exception $e) {
            $this->error('Failed to retrieve table list: ' . $e->getMessage());

            return [];
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            ++$unitIndex;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
