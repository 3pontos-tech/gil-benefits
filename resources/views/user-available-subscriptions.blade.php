@php use Filament\Support\Icons\Heroicon;use Illuminate\Support\Collection;use Illuminate\Support\Number;use TresPontosTech\Billing\Core\Entities\PlanEntity;use TresPontosTech\Billing\Core\Entities\PriceEntity;use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;use TresPontosTech\Discounts\DTOs\DiscountOffer; @endphp
@props([
    'plans',
    'audience' => PriceAudienceEnum::Subsidized,
    'offersByPlan' => [],
])

@php
    /** @var Collection<string, PlanEntity> $plans */

    // A página já filtrou os planos sem preço para esta audiência.
    $priceFor = fn (PlanEntity $plan): PriceEntity => $plan->prices
        ->firstOrFail(fn (PriceEntity $price): bool => $price->audience === $audience);

    // Sort plans by their price value (ascending)
    $sortedPlans = $plans->sortBy(fn (PlanEntity $plan): int => $priceFor($plan)->priceInCents);
@endphp


<div>
<x-filament::modal id="waiting-for-payment" :close-button="false" :close-by-clicking-away="false">
    <x-slot name="heading">Aguardando confirmação do pagamento</x-slot>

    <div wire:poll.3000ms="checkPaymentStatus" class="flex flex-col items-center gap-4 py-4 text-center">
        <x-filament::loading-indicator class="h-10 w-10 text-primary-600" />
        <p class="text-sm text-gray-500 dark:text-gray-400">
            O link de pagamento foi aberto em uma nova aba.<br>
            Esta página será atualizada automaticamente após a confirmação.
        </p>
        <x-filament::button color="gray" wire:click="cancelWaiting">
            Voltar e tentar novamente
        </x-filament::button>
    </div>
</x-filament::modal>

<div x-data="{ selectedPlan: '{{ $this->selectedPlanSlug }}' }" class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl ">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-balance mb-2">{{ __('views.subscriptions.choose_plan') }}</h1>
            <p class="text-muted-foreground text-lg">
                {{ __('views.subscriptions.description_line1') }}</br>
                {{ __('views.subscriptions.description_line2') }}
            </p>
        </div>
        <div class="grid lg:grid-cols-3 gap-8 ">
            <div class="lg:col-span-3 min-w-0 space-y-6">
                <x-filament::section
                    :heading="__('views.subscriptions.product_details')"
                    :description="__('views.subscriptions.select_employees')">
                    <form wire:submit="applyCode" class="mb-6 min-w-0">
                        <label for="discount-code" class="mb-1 block text-sm font-medium">Tenho um código</label>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <div class="min-w-0 flex-1">
                                <input
                                    id="discount-code"
                                    type="text"
                                    wire:model="discountCode"
                                    autocomplete="off"
                                    placeholder="Digite o código"
                                    class="w-full min-w-0 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm uppercase dark:border-gray-600 dark:bg-gray-900"
                                />
                                @error('discountCode')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <x-filament::button type="submit" color="gray" class="sm:w-auto">
                                Aplicar
                            </x-filament::button>
                        </div>
                    </form>

                    <div class="grid sm:grid-cols-1 lg:grid-cols-3 gap-3 mb-10">
                        @foreach($sortedPlans as $plan)
                            @php
                                /** @var PlanEntity $plan */
                                $price = $priceFor($plan);
                                $features =  [
                                    'appointments' => $price->monthlyAppointments,
                                    'whatsapp_access' => $price->whatsappEnabled,
                                    'exclusive_materials' => $price->materialsEnabled,
                                ];
                                $formatted = $price ? Number::currency(($price->priceInCents ?? 0) / 100, 'BRL', 'pt_BR') : '';
                                /** @var Collection<int, DiscountOffer> $offers */
                                $offers = $offersByPlan[$plan->slug] ?? collect();
                                /** @var DiscountOffer|null $selectedOffer */
                                $selectedOffer = $offers->firstWhere('key', $selectedOffers[$plan->slug] ?? '');
                            @endphp
                            <x-card-gradient>
                                <x-slot name="title" class="flex flex-row justify-between">
                                    <h4>{{ __( $plan->name) }}</h4>
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

                                    @if($offers->isNotEmpty())
                                        <fieldset class="mt-4 min-w-0 space-y-2">
                                            <legend class="mb-2 text-sm font-semibold">
                                                {{ $offers->count() === 1 ? '1 cupom disponível' : $offers->count() . ' cupons disponíveis' }}
                                            </legend>
                                            @foreach($offers as $offer)
                                                <label class="flex min-w-0 cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                                                    <input
                                                        type="radio"
                                                        name="offer-{{ $plan->slug }}"
                                                        value="{{ $offer->key }}"
                                                        wire:model.live="selectedOffers.{{ $plan->slug }}"
                                                        class="mt-1 shrink-0"
                                                    />
                                                    <span class="min-w-0 break-words">
                                                        <span class="block font-semibold">{{ $offer->label }}</span>
                                                        <span class="block text-gray-500">{{ $offer->summary() }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                                                <input
                                                    type="radio"
                                                    name="offer-{{ $plan->slug }}"
                                                    value=""
                                                    wire:model.live="selectedOffers.{{ $plan->slug }}"
                                                    class="shrink-0"
                                                />
                                                <span>Sem cupom</span>
                                            </label>
                                        </fieldset>
                                    @endif
                                </x-slot>
                                <x-slot name="footer" class="flex justify-between">
                                    <span>{{ __('views.subscriptions.subtotal') }}</span>
                                    <div class="flex flex-col items-end">
                                        @if($selectedOffer)
                                            <span class="text-sm text-gray-500 line-through">{{ Number::currency($price->priceInCents / 100, 'BRL', 'pt_BR') }}</span>
                                            <span class="flex items-center font-bold text-high text-xl gap-1">
                                                {{ Number::currency($selectedOffer->discountedAmountCents / 100, 'BRL', 'pt_BR') }}
                                                <span class="text-medium font-medium text-xs">{{ __('views.subscriptions.per_month') }}</span>
                                            </span>
                                            <span class="mt-1 max-w-full break-words rounded-full bg-primary-600/10 text-primary-700 px-2 py-0.5 text-right text-xs font-semibold">{{ $selectedOffer->summary() }}</span>
                                        @else
                                            <span class="flex items-center font-bold text-high text-xl gap-1">
                                                {{ $formatted }}
                                                <span class="text-medium font-medium text-xs">{{ __('views.subscriptions.per_month') }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </x-slot>
                            </x-card-gradient>
                        @endforeach
                    </div>

                    <x-slot name="footer">
                        <div x-on:click="$wire.checkout(selectedPlan, $wire.selectedOffers[selectedPlan] ?? '')">
                            <x-filament::button color="primary" size="xl" class="w-full text-base">
                                {{ __('views.subscriptions.complete_subscription') }}
                            </x-filament::button>
                        </div>
                    </x-slot>
                </x-filament::section>

            </div>
        </div>
    </div>
</div>
</div>
