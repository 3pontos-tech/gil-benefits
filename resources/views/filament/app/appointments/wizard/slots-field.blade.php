@php
    $slots = $slots instanceof Closure ? $slots($get) : $slots;
    $statePath = $getStatePath();
    $current = $getState();
    $hasDate = filled($get('date'));
@endphp

<div class="flex h-full flex-col border border-gray-200 p-4 dark:border-white/10">
    <h4 class="text-[18px] font-bold text-gray-950 dark:text-white">
        {{ __('panel-app::resources.appointments.schedule.slot.available_times') }}
    </h4>

    @if (! $hasDate)
        <p class="mt-4 text-[14px] text-gray-500 dark:text-gray-400">
            {{ __('panel-app::resources.appointments.schedule.slot.pick_date_first') }}
        </p>
    @elseif (blank($slots))
        <p class="mt-4 text-[14px] text-gray-500 dark:text-gray-400">
            {{ __('panel-app::resources.appointments.schedule.slot.no_slots') }}
        </p>
    @else
        <div class="mt-4 grid grid-cols-3 gap-2">
            @foreach ($slots as $value => $label)
                @php $isSelected = $current === $value; @endphp
                <button
                    type="button"
                    wire:click="$set('{{ $statePath }}', '{{ $value }}')"
                    @class([
                        'border px-2 py-1.5 text-center text-[14px] font-medium transition',
                        'border-danger-500 bg-danger-500 text-white' => $isSelected,
                        'border-gray-200 text-gray-950 hover:border-gray-300 dark:border-white/10 dark:text-white dark:hover:border-white/20' => ! $isSelected,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    @endif
</div>
