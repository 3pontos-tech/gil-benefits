@php use Filament\Support\Icons\Heroicon;use Illuminate\Support\Collection;use TresPontosTech\Billing\Core\Entities\PlanEntity; @endphp
@props([
    'plans'
])

@php
    /** @var Collection<string, PlanEntity> $plans */

    $tiers = $plans->map(fn (PlanEntity $plan, string $key) => [
        'label' => $key,
        'pricing' => $plan->prices->first()->metadata['price'],
        'features' => $plan->prices->first()->metadata['features'],
        'min' => 2,
        'max' => 5,
        'price_key' => $key,
    ])->toArray();

@endphp


<div
    x-data="{
        min: 5,
        qty: 5,
        tierPrice() {
            if (this.qty <= 15) { return 44.90 }
            if (this.qty <= 30) { return 34.90 }
            if (this.qty <= 70) { return 24.90 }
            return 11.90
        },
        formatBRL(v) { return 'R$ ' + Number(v).toFixed(2).replace('.', ',') },
        clamp() { if (!this.qty || this.qty < this.min) this.qty = this.min },
        inRange(min, max) { return this.qty >= min && (max === null ? true : this.qty <= max) },
        subtotal() { return this.qty * this.tierPrice() },
    }"
    x-init="clamp()"
    class=" py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl ">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-balance mb-2">Monte seu Plano</h1>
            <p class="text-muted-foreground text-lg">
                Nosso sistema é feito baseado na sua demanda.</br>
                Escolha a quantidade de assentos e finalize o Checkout!</p>
        </div>
        <div class="grid lg:grid-cols-3 gap-8 ">
            <div class="lg:col-span-2 space-y-6 space-x-10">
                <x-filament::section
                    heading="Detalhes do Produto"
                    description="Selecione o número de colaboradores para sua avaliação financeira">
                    <div class="grid sm:grid-cols-2 gap-3 mb-10">
                        @foreach($tiers as $tier)
                            <x-filament::section compact="true" wire:click="checkout('{{ $tier['price_key'] }}')"
                                                 x-bind:class="inRange({{$tier['min']}},{{$tier['max']}}) ? 'bg-primary-900/10 ring-primary-800/30' : ''">
                                <x-filament::section.heading>
                                    {{ $tier['label'] }}
                                    <x-filament::badge x-show="inRange({{$tier['min']}},{{$tier['max']}})"
                                                       badge-color="primary">Atual
                                    </x-filament::badge>
                                </x-filament::section.heading>

                                <x-filament::section.description>
                                    <span
                                        class="text-2xl font-bold">R$ {{ number_format($tier['pricing'], 2, ',') }}</span>
                                    <div class="flex flex-col gap-2">
                                        @foreach($tier['features'] as $feature)
                                            <x-filament::badge badge-color="primary">
                                                {{ $feature }}
                                            </x-filament::badge>
                                        @endforeach
                                    </div>
                                </x-filament::section.description>
                            </x-filament::section>
                        @endforeach
                    </div>

                </x-filament::section>

            </div>
            <div class="lg:col-span-1 min-h-full ">
                <div class="sticky top-8 min-h-full">
                    <x-filament::section heading="Resumo do Pedido" class="flex flex-col ">
                        <x-filament::section.description>
                            <div class="space-y-3 mb-10">
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Produto</span>
                                    <x-filament::badge>Flamma para Empresas</x-filament::badge>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Assentos</span>
                                    <x-filament::badge color="gray" x-text="qty"/>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Preço unitário</span>
                                    <x-filament::badge color="green" x-text="formatBRL(tierPrice())"/>
                                </div>

                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Subtotal</span>
                                    <span class="font-medium" x-text="formatBRL(subtotal())"></span>
                                </div>

                            </div>

                            <x-filament::button
                                x-bind:disabled="qty < min"
                                wire:click="checkout()" icon="fab-stripe" color="primary" size="xl"
                                class="w-full text-base">
                                Finalizar Assinatura
                            </x-filament::button>
                        </x-filament::section.description>
                    </x-filament::section>
                </div>
            </div>
        </div>
    </div>
</div>
