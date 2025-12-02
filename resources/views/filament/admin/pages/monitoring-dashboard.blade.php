<x-filament-panels::page>
    <div class="space-y-6">
        <!-- System Status Overview -->
        <div class="grid grid-cols-1 gap-6">
            <x-filament::section>
                <x-slot name="heading">
                    System Status Overview
                </x-slot>
                
                <div class="space-y-4">
                    @php
                        $dashboardData = $this->getDashboardData();
                        $systemHealth = $dashboardData['system_health'];
                        $statusColor = match($systemHealth['overall_status']) {
                            'healthy' => 'success',
                            'degraded' => 'warning',
                            'critical' => 'danger',
                            default => 'gray'
                        };
                    @endphp
                    
                    <div class="flex items-center space-x-4">
                        <x-filament::badge :color="$statusColor" size="lg">
                            {{ ucfirst($systemHealth['overall_status']) }}
                        </x-filament::badge>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Last checked: {{ \Carbon\Carbon::parse($systemHealth['last_check'])->diffForHumans() }}
                        </span>
                    </div>

                    @if(!empty($systemHealth['issues']))
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Active Issues:</h4>
                            <div class="space-y-2">
                                @foreach($systemHealth['issues'] as $issue)
                                    <div class="flex items-center space-x-2 text-sm">
                                        <x-heroicon-m-exclamation-triangle class="w-4 h-4 text-amber-500" />
                                        <span class="text-gray-700 dark:text-gray-300">
                                            <strong>{{ ucfirst($issue['component']) }}:</strong> {{ $issue['issue'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        </div>

        <!-- Performance Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-filament::section>
                <x-slot name="heading">
                    Performance Summary
                </x-slot>
                
                @php
                    $performance = $dashboardData['performance_metrics'];
                @endphp
                
                <div class="space-y-3">
                    @if($performance['response_times']['average_ms'])
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Average Response Time:</span>
                            <span class="text-sm font-medium">{{ $performance['response_times']['average_ms'] }}ms</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">95th Percentile:</span>
                            <span class="text-sm font-medium">{{ $performance['response_times']['p95_ms'] }}ms</span>
                        </div>
                    @endif
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Memory Usage:</span>
                        <span class="text-sm font-medium">
                            {{ $performance['memory_usage']['current_mb'] }}MB ({{ $performance['memory_usage']['usage_percentage'] }}%)
                        </span>
                    </div>
                    
                    @if($performance['database_performance']['connection_time_ms'])
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Database Response:</span>
                            <span class="text-sm font-medium">{{ $performance['database_performance']['connection_time_ms'] }}ms</span>
                        </div>
                    @endif
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    Database Analytics
                </x-slot>
                
                @php
                    $database = $dashboardData['database_analytics'];
                    $queryStats = $database['query_statistics'];
                @endphp
                
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Queries:</span>
                        <span class="text-sm font-medium">{{ $queryStats['total_queries'] }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Average Time:</span>
                        <span class="text-sm font-medium">{{ $queryStats['average_time_ms'] }}ms</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Slow Queries:</span>
                        <span class="text-sm font-medium {{ $queryStats['slow_queries'] > 0 ? 'text-amber-600' : '' }}">
                            {{ $queryStats['slow_queries'] }} ({{ $queryStats['slow_query_percentage'] }}%)
                        </span>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- User Activity Summary -->
        <div class="grid grid-cols-1 gap-6">
            <x-filament::section>
                <x-slot name="heading">
                    User Activity Summary
                </x-slot>
                
                @php
                    $activity = $dashboardData['user_activity'];
                    $engagement = $activity['engagement_metrics'];
                @endphp
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $engagement['unique_active_users'] }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Active Users</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $engagement['total_sessions'] }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Sessions</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $engagement['average_sessions_per_user'] }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Avg Sessions/User</div>
                    </div>
                </div>

                @if(!empty($activity['top_actions']))
                    <div class="mt-6">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Top Actions:</h4>
                        <div class="space-y-2">
                            @foreach(array_slice($activity['top_actions'], 0, 5, true) as $action => $count)
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $action }}</span>
                                    <span class="font-medium">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-filament::section>
        </div>

        <!-- Recommendations -->
        @if(!empty($dashboardData['recommendations']))
            <div class="grid grid-cols-1 gap-6">
                <x-filament::section>
                    <x-slot name="heading">
                        System Recommendations
                    </x-slot>
                    
                    <div class="space-y-4">
                        @foreach(array_slice($dashboardData['recommendations'], 0, 5) as $recommendation)
                            @php
                                $priorityColor = match($recommendation['priority']) {
                                    'critical' => 'danger',
                                    'high' => 'danger',
                                    'medium' => 'warning',
                                    'low' => 'success',
                                    default => 'gray'
                                };
                            @endphp
                            
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-start space-x-3">
                                    <x-filament::badge :color="$priorityColor" size="sm">
                                        {{ ucfirst($recommendation['priority']) }}
                                    </x-filament::badge>
                                    <div class="flex-1">
                                        <h5 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $recommendation['title'] ?? $recommendation['issue'] ?? 'Recommendation' }}
                                        </h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $recommendation['recommendation'] ?? $recommendation['description'] ?? 'No description available' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>
        @endif
    </div>
</x-filament-panels::page>