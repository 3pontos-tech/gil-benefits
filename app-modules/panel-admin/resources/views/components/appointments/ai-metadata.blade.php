@props([
    'record' => null,
])

@if($record?->model_used)
    <x-filament::section
        :heading="__('appointments::resources.appointments.infolist.ai_generation')"
        icon="heroicon-o-cpu-chip"
        collapsible
        collapsed
    >
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">
                    {{ __('appointments::resources.appointments.infolist.ai.model_used') }}
                </dt>
                <dd>
                    <x-filament::badge size="sm" color="gray">{{ $record->model_used }}</x-filament::badge>
                </dd>
            </div>

            @if($record->input_tokens)
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">
                        {{ __('appointments::resources.appointments.infolist.ai.input_tokens') }}
                    </dt>
                    <dd class="font-mono text-gray-900 dark:text-white">
                        {{ number_format($record->input_tokens) }}
                    </dd>
                </div>
            @endif

            @if($record->output_tokens)
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">
                        {{ __('appointments::resources.appointments.infolist.ai.output_tokens') }}
                    </dt>
                    <dd class="font-mono text-gray-900 dark:text-white">
                        {{ number_format($record->output_tokens) }}
                    </dd>
                </div>
            @endif

            @if($record->input_tokens && $record->output_tokens)
                <div class="flex justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
                    <dt class="font-medium text-gray-700 dark:text-gray-300">
                        {{ __('appointments::resources.appointments.infolist.ai.total_tokens') }}
                    </dt>
                    <dd class="font-mono font-medium text-gray-900 dark:text-white">
                        {{ number_format($record->input_tokens + $record->output_tokens) }}
                    </dd>
                </div>
            @endif
        </dl>
    </x-filament::section>
@endif
