<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Basic Information</h3>
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg space-y-2">
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700 dark:text-gray-300">User:</span>
                    <span class="text-gray-900 dark:text-white">{{ $record['user_email'] ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Action:</span>
                    <span class="text-gray-900 dark:text-white">{{ $record['action'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Result:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $record['granted'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                        {{ $record['granted'] ? 'Granted' : 'Denied' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Timestamp:</span>
                    <span class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($record['created_at'])->format('Y-m-d H:i:s') }}</span>
                </div>
            </div>
        </div>

        <div class="space-y-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Resource Information</h3>
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg space-y-2">
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Model Type:</span>
                    <span class="text-gray-900 dark:text-white">{{ $record['model_type'] ? class_basename($record['model_type']) : 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Model ID:</span>
                    <span class="text-gray-900 dark:text-white">{{ $record['model_id'] ?? 'N/A' }}</span>
                </div>
                @if($record['reason'])
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Reason:</span>
                    <span class="text-gray-900 dark:text-white">{{ $record['reason'] }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Request Information</h3>
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg space-y-2">
            <div class="flex justify-between">
                <span class="font-medium text-gray-700 dark:text-gray-300">IP Address:</span>
                <span class="text-gray-900 dark:text-white">{{ $record['ip_address'] ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium text-gray-700 dark:text-gray-300">User Agent:</span>
                <span class="text-gray-900 dark:text-white text-sm break-all">{{ $record['user_agent'] ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    @if(!empty($context))
    <div class="space-y-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Additional Context</h3>
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            @if(isset($context['is_partner_collaborator']) && $context['is_partner_collaborator'])
            <div class="mb-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                Partner Collaborator
            </div>
            @endif

            @if(isset($context['partner_company_id']))
            <div class="flex justify-between mb-2">
                <span class="font-medium text-gray-700 dark:text-gray-300">Partner Company ID:</span>
                <span class="text-gray-900 dark:text-white">{{ $context['partner_company_id'] }}</span>
            </div>
            @endif

            @if(isset($context['user_roles']) && is_array($context['user_roles']))
            <div class="mb-2">
                <span class="font-medium text-gray-700 dark:text-gray-300">User Roles:</span>
                <div class="mt-1 flex flex-wrap gap-1">
                    @foreach($context['user_roles'] as $role)
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ is_array($role) ? ($role['company_name'] ?? 'Unknown') . ': ' . ($role['role'] ?? 'Unknown') : $role }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

            @if(isset($context['type']))
            <div class="flex justify-between mb-2">
                <span class="font-medium text-gray-700 dark:text-gray-300">Event Type:</span>
                <span class="text-gray-900 dark:text-white">{{ ucwords(str_replace('_', ' ', $context['type'])) }}</span>
            </div>
            @endif

            @if(isset($context['severity']))
            <div class="flex justify-between mb-2">
                <span class="font-medium text-gray-700 dark:text-gray-300">Severity:</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $context['severity'] === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($context['severity'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200') }}">
                    {{ ucfirst($context['severity']) }}
                </span>
            </div>
            @endif

            @if(isset($context['route_name']))
            <div class="flex justify-between mb-2">
                <span class="font-medium text-gray-700 dark:text-gray-300">Route:</span>
                <span class="text-gray-900 dark:text-white text-sm">{{ $context['route_name'] }}</span>
            </div>
            @endif

            @if(isset($context['panel_id']))
            <div class="flex justify-between mb-2">
                <span class="font-medium text-gray-700 dark:text-gray-300">Panel:</span>
                <span class="text-gray-900 dark:text-white">{{ ucfirst($context['panel_id']) }}</span>
            </div>
            @endif

            @if(count($context) > 10)
            <details class="mt-4">
                <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">Show Full Context</summary>
                <pre class="mt-2 text-xs bg-gray-100 dark:bg-gray-900 p-2 rounded overflow-auto max-h-40">{{ json_encode($context, JSON_PRETTY_PRINT) }}</pre>
            </details>
            @endif
        </div>
    </div>
    @endif
</div>