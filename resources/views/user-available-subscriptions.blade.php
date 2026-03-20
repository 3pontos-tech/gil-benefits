@php use Filament\Support\Icons\Heroicon;use Illuminate\Support\Collection;use TresPontosTech\Billing\Core\Entities\PlanEntity; @endphp
@props([
    'plans'
])

@php
    /** @var Collection<string, PlanEntity> $plans */

    // Sort plans by their first price value (ascending)
    $sortedPlans = $plans->sort(function (PlanEntity $a, PlanEntity $b): int {
        $firstPrice = $a->prices->first();
        $secondPrice = $b->prices->first();
        return $firstPrice?->priceInCents > $secondPrice->priceInCents;
    });
@endphp


<div
    x-data="{ selectedPlan: $wire.entangle('selectedPlan').live }"
    class=" py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl ">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-balance mb-2">{{ __('views.subscriptions.choose_plan') }}</h1>
            <p class="text-muted-foreground text-lg">
                {{ __('views.subscriptions.description_line1') }}</br>
                {{ __('views.subscriptions.description_line2') }}
            </p>
        </div>
        <div class="grid lg:grid-cols-3 gap-8 ">
            <div class="lg:col-span-3 space-y-6 space-x-10">
                <x-filament::section
                    :heading="__('views.subscriptions.product_details')"
                    :description="__('views.subscriptions.select_employees')">
                    <div class="grid sm:grid-cols-1 lg:grid-cols-3 gap-3 mb-10">
                        @foreach($sortedPlans as $plan)
                            @php
                                /** @var PlanEntity $plan */
                                $price = $plan->prices->first();
                                $features =  [
                                    'appointments' => $price->monthlyAppointments,
                                    'whatsapp_access' => $price->whatsappEnabled,
                                    'exclusive_materials' => $price->materialsEnabled,
                                ];
                                $formatted = $price ? Number::currency(($price->priceInCents ?? 0) / 100, 'BRL') : '';
                            @endphp
                            <x-card-gradient>
                                <x-slot name="title" class="flex flex-row justify-between">
                                    <h4>{{ __($plan->name) }}</h4>
                                    <span class="inline-flex items-center">
                                        <label class="relative inline-flex items-center cursor-pointer select-none" :aria-checked="selectedPlan === '{{ $plan->slug }}'" role="radio">
                                            <input
                                                type="radio"
                                                name="plan"
                                                class="sr-only"
                                                x-model="selectedPlan"
                                                value="{{ $plan->slug }}"
                                            />
                                            <span
                                                class="grid size-7 place-items-center rounded-full border transition-all duration-300 ease-out"
                                                :class="selectedPlan === '{{ $plan->slug }}' ? 'border-primary-600 bg-primary-600/10 ring-4 ring-primary-600/10' : 'border-gray-300 dark:border-gray-600'"
                                                @click.prevent="selectedPlan = '{{ $plan->slug }}'"
                                            >
                                                <svg
                                                    x-show="selectedPlan === '{{ $plan->slug }}'"
                                                    x-transition:enter="transition ease-out duration-200"
                                                    x-transition:enter-start="opacity-0 scale-75"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-150"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-75"
                                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                                    class="size-4 text-primary-700 dark:text-primary-400"
                                                    aria-hidden="true"
                                                >
                                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.3a1 1 0 0 1-1.43.01L3.29 9.53a1 1 0 1 1 1.42-1.41l3.06 3.02 6.49-6.58a1 1 0 0 1 1.444-.27z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                        </label>
                                    </span>

                                </x-slot>
                                <x-slot name="description">
                                    @foreach($features as $key => $feature)
                                        <div class="flex">
                                            <span class="text-gray-500">{{ __('all.' . $key) }}</span>
                                            @if(is_bool($feature))
                                                @if($feature)
                                                    <span class="ml-auto dark:text-white font-bold text-gray-950">
                                                        {{ __('views.subscriptions.yes') }}
                                                    </span>
                                                @else
                                                    <span class="ml-auto dark:text-gray-400 font-bold text-gray-950">
                                                        --
                                                    </span>
                                                @endif
                                            @else
                                                <span class="ml-auto dark:text-white font-bold text-gray-950">
                                                    {{ $feature }}
                                                </span>
                                            @endif

                                        </div>
                                    @endforeach
                                </x-slot>
                                <x-slot name="footer" class="flex justify-between">
                                    <span>{{ __('views.subscriptions.subtotal') }}</span>
                                    <div class="flex">
                                        <span class="flex items-center font-bold text-high text-xl gap-1">
                                            {{ $formatted }}
                                            <span class="text-medium font-medium text-xs">{{ __('views.subscriptions.per_month') }}</span>
                                        </span>
                                    </div>
                                </x-slot>
                            </x-card-gradient>
                        @endforeach
                    </div>

                    <x-slot name="footer">
                        <x-filament::button
                            wire:click="checkout()" icon="fab-stripe" color="primary" size="xl"
                            class="w-full text-base">
                            {{ __('views.subscriptions.complete_subscription') }}
                        </x-filament::button>
                    </x-slot>
                </x-filament::section>

            </div>
        </div>
    </div>
</div>
