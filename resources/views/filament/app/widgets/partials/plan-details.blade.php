@php /** @var \TresPontosTech\App\DTOs\PlanSummary $plan */ @endphp
<div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
    @if($plan->description)
        <p>{{ $plan->description }}</p>
    @endif

    @if(count($plan->features) > 0)
        <ul class="space-y-2">
            @foreach($plan->features as $feature)
                <li class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-check-circle" class="size-5 shrink-0 text-primary-600 dark:text-primary-400" />
                    <span>{{ $feature }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
