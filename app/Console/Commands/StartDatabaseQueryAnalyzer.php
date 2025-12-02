<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Monitoring\DatabaseQueryAnalyzer;
use Illuminate\Console\Command;

class StartDatabaseQueryAnalyzer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:start-query-analyzer 
                            {--stop : Stop the query analyzer}
                            {--status : Show analyzer status}
                            {--clear : Clear analyzer statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start, stop, or manage the database query analyzer';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseQueryAnalyzer $analyzer): int
    {
        if ($this->option('stop')) {
            return $this->stopAnalyzer($analyzer);
        }

        if ($this->option('status')) {
            return $this->showStatus($analyzer);
        }

        if ($this->option('clear')) {
            return $this->clearStatistics($analyzer);
        }

        return $this->startAnalyzer($analyzer);
    }

    private function startAnalyzer(DatabaseQueryAnalyzer $analyzer): int
    {
        $this->info('🔍 Starting database query analyzer...');
        
        try {
            $analyzer->startMonitoring();
            $this->info('✅ Database query analyzer started successfully');
            $this->line('The analyzer will now monitor all database queries and collect performance metrics.');
            $this->line('Use --status to check the current status or --stop to stop monitoring.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to start query analyzer: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function stopAnalyzer(DatabaseQueryAnalyzer $analyzer): int
    {
        $this->info('🛑 Stopping database query analyzer...');
        
        try {
            $analyzer->stopMonitoring();
            $this->info('✅ Database query analyzer stopped successfully');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to stop query analyzer: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function showStatus(DatabaseQueryAnalyzer $analyzer): int
    {
        $this->info('📊 Database Query Analyzer Status');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Get current statistics
        $stats = $analyzer->getQueryStatistics();
        $slowQueries = $analyzer->getSlowQueries();

        $this->line(sprintf('Total Queries: <fg=cyan>%d</fg=cyan>', $stats['total_queries'] ?? 0));
        $this->line(sprintf('Average Time: <fg=cyan>%s ms</fg=cyan>', $stats['average_time'] ?? 0));
        $this->line(sprintf('Slow Queries: <fg=yellow>%d</fg=yellow> (%s%%)', 
            $stats['slow_queries'] ?? 0, 
            $stats['slow_query_percentage'] ?? 0
        ));

        if (!empty($stats['by_type'])) {
            $this->newLine();
            $this->line('<fg=yellow>Query Types:</fg=yellow>');
            foreach ($stats['by_type'] as $type => $data) {
                $avgTime = $data['count'] > 0 ? round($data['total_time'] / $data['count'], 2) : 0;
                $this->line(sprintf('  %s: %d queries (%s ms avg)', $type, $data['count'], $avgTime));
            }
        }

        if (!empty($slowQueries)) {
            $this->newLine();
            $this->line('<fg=red>Recent Slow Queries:</fg=red>');
            foreach (array_slice($slowQueries, -5) as $query) {
                $this->line(sprintf('  %s ms: %s', 
                    $query['time_ms'], 
                    substr($query['sql'], 0, 80) . '...'
                ));
            }
        }

        // Show recommendations
        $recommendations = $analyzer->analyzeQueryPatterns();
        if (!empty($recommendations)) {
            $this->newLine();
            $this->line('<fg=cyan>💡 Recommendations:</fg=cyan>');
            foreach (array_slice($recommendations, 0, 3) as $rec) {
                $this->line(sprintf('  <fg=yellow>[%s]</fg=yellow> %s', 
                    strtoupper($rec['priority']), 
                    $rec['recommendation']
                ));
            }
        }

        return Command::SUCCESS;
    }

    private function clearStatistics(DatabaseQueryAnalyzer $analyzer): int
    {
        $this->info('🧹 Clearing query analyzer statistics...');
        
        try {
            $analyzer->clearStatistics();
            $this->info('✅ Query analyzer statistics cleared successfully');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to clear statistics: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
