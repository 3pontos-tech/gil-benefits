@php
    /** @var string $actionLabel */
    /** @var \Filament\Support\Icons\Heroicon $actionIcon */
    /** @var array<int|string, string> $actionColor */
    /** @var string $adminName */
    /** @var string $happenedAt */
    /** @var array<int, array{label: string, value: string}> $changes */
@endphp

<div class="space-y-4">
    <x-filament::section :icon="$actionIcon" :icon-color="$actionColor">
        <dl class="space-y-3 text-sm">
            <div class="flex items-center justify-between gap-4">
                <dt class="text-gray-500 dark:text-gray-400">
                    {{ __('panel-admin::resources.appointments.history.labels.action') }}
                </dt>
                <dd class="text-right font-medium text-gray-950 dark:text-white">{{ $actionLabel }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-gray-500 dark:text-gray-400">
                    {{ __('panel-admin::resources.appointments.history.labels.performed_by') }}
                </dt>
                <dd class="text-right text-gray-950 dark:text-white">{{ $adminName }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-gray-500 dark:text-gray-400">
                    {{ __('panel-admin::resources.appointments.history.labels.happened_at') }}
                </dt>
                <dd class="text-right text-gray-950 dark:text-white">{{ $happenedAt }}</dd>
            </div>
        </dl>
    </x-filament::section>

    @if(filled($changes))
        <x-filament::section :heading="$actionLabel">
            <dl class="space-y-3 text-sm">
                @foreach($changes as $change)
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">{{ $change['label'] }}</dt>
                        <dd class="text-right font-medium text-gray-950 dark:text-white">{{ $change['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-filament::section>
    @endif
</div>
