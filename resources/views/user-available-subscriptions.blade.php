@php use Filament\Support\Icons\Heroicon;use Illuminate\Support\Collection;use TresPontosTech\Billing\Core\Entities\PlanEntity; @endphp
@props([
    'plans'
])

@php
    /** @var Collection<string, PlanEntity> $plans */

    $tiers = $plans

    ->map(fn (PlanEntity $plan, string $key) => [
        'label' => $plan->name,
        'slug' => $plan->slug,
        'pricing_in_cents' => $plan->prices->first()->priceInCents,
        'pricing' => Number::currency($plan->prices->first()->priceInCents / 100, 'BRL'),
        'features' => $plan->prices->first()->metadata['features'],
        'price_key' => $plan->slug,
    ])
    ->sort(fn ($a, $b) => $a['pricing_in_cents'] > $b['pricing_in_cents'])
    ->toArray();

    $defaultPlanSlug = $tiers[array_key_first($tiers)]['slug'] ?? '';
@endphp


<div
    x-data="{ selectedPlan: $wire.entangle('selectedPlan').live }"
    class=" py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl ">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-balance mb-2">Escolha seu plano</h1>
            <p class="text-muted-foreground text-lg">
                Nosso sistema é feito baseado na sua demanda.</br>
                Escolha o que mais faz sentido pro seu momento atual!
            </p>
        </div>
        <div class="grid lg:grid-cols-3 gap-8 ">
            <div class="lg:col-span-3 space-y-6 space-x-10">
                <x-filament::section
                    heading="Detalhes do Produto"
                    description="Selecione o número de colaboradores para sua avaliação financeira">
                    <div class="grid sm:grid-cols-1 lg:grid-cols-3 gap-3 mb-10">
                        @foreach($tiers as $tier)
                            <x-card-gradient>
                                <x-slot name="title" class="flex flex-row justify-between">
                                    <h4>{{ __($tier['label']) }}</h4>
                                    <span class="inline-flex items-center">
                                        <label class="relative inline-flex items-center cursor-pointer select-none" :aria-checked="selectedPlan === '{{ $tier['slug'] }}'" role="radio">
                                            <input
                                                type="radio"
                                                name="plan"
                                                class="sr-only"
                                                x-model="selectedPlan"
                                                value="{{ $tier['slug'] }}"
                                            />
                                            <span
                                                class="grid size-7 place-items-center rounded-full border transition-all duration-300 ease-out"
                                                :class="selectedPlan === '{{ $tier['slug'] }}' ? 'border-primary-600 bg-primary-600/10 ring-4 ring-primary-600/10' : 'border-gray-300 dark:border-gray-600'"
                                                @click.prevent="selectedPlan = '{{ $tier['slug'] }}'"
                                            >
                                                <svg
                                                    x-show="selectedPlan === '{{ $tier['slug'] }}'"
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
                                    @foreach($tier['features'] as $key => $feature)
                                        <div class="flex">
                                            <span class="text-gray-500">{{ __('all.' . $key) }}</span>
                                            @if(is_bool($feature))
                                                @if($feature)
                                                    <span class="ml-auto dark:text-white font-bold text-gray-950">
                                                        Sim
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
                                    <span>Subtotal</span>
                                    <div class="flex">
                                        <span class="flex items-center font-bold text-high text-xl gap-1">
                                            {{ $tier['pricing'] }}
                                            <span class="text-medium font-medium text-xs">/mês</span>
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
                            Finalizar Assinatura
                        </x-filament::button>
                    </x-slot>
                </x-filament::section>

            </div>
        </div>
    </div>
</div>
