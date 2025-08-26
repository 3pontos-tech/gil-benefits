@php
    $slots = $slots instanceof Closure ? $slots($get) : $slots;
    $statePath = $getStatePath();
    $current = $getState();
@endphp

<div class="grid grid-cols-3 gap-2 my-2">
    @foreach($slots as $value => $label)
        <x-filament::button
            size="sm"
            class="w-full"
            color="{{ $current === $value ? 'primary' : 'gray' }}"
            wire:click="$set('{{ $statePath }}', '{{ $value }}')"
        >
            {{ $label }}
        </x-filament::button>
    @endforeach
</div>

