@props([
    'benefitTitles' => [],
    'plans' => [],
])

@php
    $rowCount = count($benefitTitles) + 1;
    $planCount = count($plans);
@endphp

<div class="w-full h-full bg-elevation-01dp border border-outline-light rounded-xl text-medium
            grid grid-cols-1
            gap-4 lg:gap-x-12 p-4
            lg:grid-cols-[1.5fr_repeat(var(--plan-count),1fr)]
            lg:grid-rows-[repeat(var(--row-count),auto)]"

     style="--row-count: {{ $rowCount }}; --plan-count: {{ $planCount }};">

    <div class="hidden lg:grid gap-8 lg:grid-rows-subgrid lg:row-span-full">
        @foreach ($benefitTitles as $title)
            <h2 class="font-bold lg:row-start-[var(--start-line)]"
                style="--start-line: {{ $loop->index + 2 }};">
                {{ $title }}
            </h2>
        @endforeach
    </div>

    @foreach ($plans as $plan)
        <div class="grid gap-8 lg:grid-rows-subgrid lg:row-span-full">
            <h3 class="font-bold text-xl lg:text-2xl">{{ $plan['title'] }}</h3>
            @foreach ($plan['benefits'] as $benefit)
                <div>
                    <p class="lg:hidden">{{ $benefitTitles[$loop->index] }}</p>
                    <p class="text-high font-bold">{{ $benefit }}</p>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
