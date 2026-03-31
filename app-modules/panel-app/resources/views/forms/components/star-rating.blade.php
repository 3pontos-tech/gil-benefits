<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            value: $wire.entangle('{{ $getStatePath() }}'),
            hovered: 0,
        }"
        class="flex gap-1"
    >
        @foreach (range(1, 5) as $star)
            <button
                type="button"
                x-on:click="value = {{ $star }}"
                x-on:mouseenter="hovered = {{ $star }}"
                x-on:mouseleave="hovered = 0"
                x-bind:style="{
                    color: hovered >= {{ $star }} || (hovered === 0 && value >= {{ $star }})
                        ? '#fbbf24'
                        : '#d1d5db'
                }"
                class="transition-colors duration-100 focus:outline-none"
            >
                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                </svg>
            </button>
        @endforeach

        <input type="hidden" x-bind:value="value" />
    </div>
</x-dynamic-component>
