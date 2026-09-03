{{-- Detalhe de utilização por colaborador (STORY-241, cenário 3). --}}
<div class="fi-ta-ctn divide-y divide-gray-200 dark:divide-white/10">
    @forelse ($users as $user)
        <div class="flex items-center justify-between gap-4 px-1 py-2">
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $user->name }}</p>
                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
            </div>

            <x-filament::badge :color="$user->statusColor" class="shrink-0">
                {{ $user->statusLabel }}
            </x-filament::badge>
        </div>
    @empty
        <p class="py-4 text-sm text-gray-500 dark:text-gray-400">
            {{ __('panel-admin::widgets.financial.usage.detail_empty') }}
        </p>
    @endforelse
</div>
