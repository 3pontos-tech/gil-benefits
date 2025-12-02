<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            System Monitoring Dashboard
        </x-slot>

        <div class="space-y-6">
            {{-- System Health Overview --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($dashboardData['system_health']['components'] as $component => $status)
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 capitalize">
                                    {{ $component }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Status
                                </p>
                            </div>
                            <div class="flex items-center">
                                @if($status === 'healthy')
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                @elseif($status === 'degraded')
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                @elseif($status === 'critical')
                                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                @else
                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="text-sm font-semibold 
                                @if($status === 'healthy') text-green-600 dark:text-green-400
                                @elseif($status === 'degraded') text-yellow-600 dark:text-yellow-400
                                @elseif($status === 'critical') text-red-600 dark:text-red-400
                                @else text-gray-600 dark:text-gray-400 @endif
                            ">
                                {{ ucfirst($status) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Performance Metrics --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Memory Usage --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Memory Usage</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                                {{ $dashboardData['performance_metrics']['memory_usage']['usage_percentage'] ?? 0 }}%
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $dashboardData['performance_metrics']['memory_usage']['current_mb'] ?? 0 }} MB used
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Response Time --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Avg Response Time</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                                {{ $dashboardData['performance_metrics']['response_times']['average_ms'] ?? 'N/A' }}
                                @if($dashboardData['performance_metrics']['response_times']['average_ms'])
                                    ms
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                P95: {{ $dashboardData['performance_metrics']['response_times']['p95_ms'] ?? 'N/A' }}ms
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Database Performance --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Database</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                                {{ $dashboardData['performance_metrics']['database_performance']['connection_time_ms'] ?? 'N/A' }}
                                @if($dashboardData['performance_metrics']['database_performance']['connection_time_ms'])
                                    ms
                                @endif
                            </p>
                            <p class="text-xs 
                                @if(($dashboardData['performance_metrics']['database_performance']['status'] ?? '') === 'connected') text-green-500 
                                @else text-red-500 @endif
                            ">
                                {{ ucfirst($dashboardData['performance_metrics']['database_performance']['status'] ?? 'unknown') }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Active Users --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Active Users</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                                {{ $dashboardData['user_activity']['engagement_metrics']['unique_active_users'] ?? 0 }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $dashboardData['user_activity']['engagement_metrics']['total_sessions'] ?? 0 }} sessions
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Database Analytics --}}
            @if(!empty($dashboardData['database_analytics']['query_statistics']))
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Database Analytics</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $dashboardData['database_analytics']['query_statistics']['total_queries'] }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Queries</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $dashboardData['database_analytics']['query_statistics']['average_time_ms'] }}ms
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Average Time</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $dashboardData['database_analytics']['query_statistics']['slow_queries'] }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Slow Queries</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold 
                                @if($dashboardData['database_analytics']['query_statistics']['slow_query_percentage'] > 10) text-red-600 dark:text-red-400
                                @elseif($dashboardData['database_analytics']['query_statistics']['slow_query_percentage'] > 5) text-yellow-600 dark:text-yellow-400
                                @else text-green-600 dark:text-green-400 @endif
                            ">
                                {{ $dashboardData['database_analytics']['query_statistics']['slow_query_percentage'] }}%
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Slow Query Rate</p>
                        </div>
                    </div>

                    @if(!empty($dashboardData['database_analytics']['recent_slow_queries']))
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">Recent Slow Queries</h4>
                            <div class="space-y-2">
                                @foreach(array_slice($dashboardData['database_analytics']['recent_slow_queries'], 0, 3) as $query)
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded p-3">
                                        <div class="flex justify-between items-start mb-1">
                                            <span class="text-sm font-medium text-red-600 dark:text-red-400">
                                                {{ $query['time_ms'] }}ms
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($query['timestamp'])->diffForHumans() }}
                                            </span>
                                        </div>
                                        <code class="text-xs text-gray-700 dark:text-gray-300 break-all">
                                            {{ Str::limit($query['sql'], 100) }}
                                        </code>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Recent Activity --}}
            @if(!empty($dashboardData['user_activity']['recent_activity']))
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Recent Activity</h3>
                    
                    <div class="space-y-3">
                        @foreach(array_slice($dashboardData['user_activity']['recent_activity'], 0, 5) as $activity)
                            <div class="flex items-center space-x-3">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        @if($activity['user_email'])
                                            <span class="font-medium">{{ $activity['user_email'] }}</span>
                                        @else
                                            <span class="text-gray-500">Anonymous user</span>
                                        @endif
                                        performed <span class="font-medium">{{ $activity['action'] }}</span>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($activity['timestamp'])->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Recommendations --}}
            @if(!empty($dashboardData['recommendations']))
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Recommendations</h3>
                    
                    <div class="space-y-3">
                        @foreach(array_slice($dashboardData['recommendations'], 0, 5) as $recommendation)
                            <div class="flex items-start space-x-3 p-3 rounded-lg
                                @if($recommendation['priority'] === 'critical') bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800
                                @elseif($recommendation['priority'] === 'high') bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800
                                @elseif($recommendation['priority'] === 'medium') bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800
                                @else bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 @endif
                            ">
                                <div class="w-2 h-2 rounded-full mt-2
                                    @if($recommendation['priority'] === 'critical') bg-red-500
                                    @elseif($recommendation['priority'] === 'high') bg-red-500
                                    @elseif($recommendation['priority'] === 'medium') bg-yellow-500
                                    @else bg-blue-500 @endif
                                "></div>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="text-xs font-medium px-2 py-1 rounded
                                            @if($recommendation['priority'] === 'critical') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                            @elseif($recommendation['priority'] === 'high') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                            @elseif($recommendation['priority'] === 'medium') bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                            @else bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 @endif
                                        ">
                                            {{ strtoupper($recommendation['priority']) }}
                                        </span>
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $recommendation['title'] ?? $recommendation['issue'] ?? 'Recommendation' }}
                                        </h4>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $recommendation['recommendation'] ?? $recommendation['description'] ?? 'No description available' }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- System Issues --}}
            @if(!empty($dashboardData['system_health']['issues']))
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-red-200 dark:border-red-800">
                    <h3 class="text-lg font-medium text-red-900 dark:text-red-100 mb-4">System Issues</h3>
                    
                    <div class="space-y-2">
                        @foreach($dashboardData['system_health']['issues'] as $issue)
                            <div class="flex items-center space-x-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-red-900 dark:text-red-100">
                                        {{ $issue['component'] }}: {{ $issue['issue'] }}
                                    </p>
                                    <p class="text-xs text-red-700 dark:text-red-300">
                                        Status: {{ ucfirst($issue['status']) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>