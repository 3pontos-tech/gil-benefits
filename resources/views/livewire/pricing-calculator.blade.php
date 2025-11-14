<div class="flex flex-col text-medium w-full gap-8">
    <div class="flex flex-col gap-4 ml-8 lg:ml-12">
        <h2 class="text-high font-bold text-xl lg:text-2xl">Adicionar colaboradores</h2>
        <p>
            O valor de acesso é cobrado por colaborador — quanto mais funcionários incluídos, mais econômica fica a assinatura.
        </p>
    </div>

    <div
        class="flex flex-col bg-elevation-01dp border border-outline-light p-4 lg:p-8 gap-8"

        x-data="{
            tiers: $wire.planTiers,
            sliderValue: 1,
            min: 1,
            max: 151,

            currentPrice: 0,
            totalCost: 0,
            activeTierId: null,

            updatePricing() {
                let currentTier = null;

                for (const tier of this.tiers) {
                    if (this.sliderValue >= tier.min && this.sliderValue <= tier.max) {
                        currentTier = tier;
                        break;
                    }
                }

                this.currentPrice = currentTier.price;
                this.activeTierId = currentTier.id;

                this.totalCost = this.sliderValue * this.currentPrice;
            }
        }"

        x-init="
            const updateGradient = () => {
                const percent = ((sliderValue - min) * 100) / (max - min);
                if ($refs.slider) {
                    $refs.slider.style.setProperty('--progress-percent', percent + '%');
                }
            };

            updatePricing();
            updateGradient();

            $watch('sliderValue', (value) => {
                if (value === '' || value === null) {
                    sliderValue = min;
                    return;
                }
                if (value > max) sliderValue = max;

                updatePricing();
                updateGradient();
            });
        "

        x-cloak
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
            <div class="order-1 flex flex-col gap-1">
                <h2 class="font-medium">Quantos colaboradores?</h2>
                <p class="font-bold text-high text-xl sm:text-2xl">
                    <div class="gradient-wrapper max-w-56">
                        <input
                            x-model.number="sliderValue"
                            type="number"
                            class="input-gradient-border p-1 font-bold border border-outline-dark text-high text-xl sm:text-2xl rounded-lg w-full"
                        />
                    </div>
                </p>
            </div>

            <div class="sm:justify-self-end order-3 sm:order-2 flex flex-col gap-1">
                <h2 class="font-medium">Custo mensal</h2>
                <p class="font-bold text-high text-xl sm:text-2xl">
                    R$ <span x-text="totalCost.toLocaleString('pt-BR', {minimumFractionDigits: 2})"></span>
                </p>
            </div>

            <div class="col-span-2 order-2 sm:order-3 flex flex-col gap-2">
                <div class="font-medium flex justify-between">
                    <span x-text="min"></span>
                    <span><span x-text="max - 1"></span>+</span>
                </div>

                <input
                    type="range"
                    :min="min"
                    :max="max"
                    class="slider"
                    x-ref="slider"
                    x-model.number="sliderValue"
                >
            </div>
        </div>

        <div class="w-full grid grid-cols-1 lg:grid-cols-5 gap-8">
            @foreach($planTiers as $tier)
                <div
                    wire:key="tier-{{ $tier['id'] }}"
                    class="flex flex-col gap-4 p-4 text-medium"
                    :class="activeTierId === {{ $tier['id'] }} ?
                        'border-brand-primary border rounded-lg lg:scale-105 transition-all ease-in-out duration-500' :
                        'border border-outline-light'"
                >
                    <h2 class="font-bold text-center">
                        @if($loop->last)
                            +{{ $tier['max'] - 1 }} Colaboradores
                        @else
                            {{ $tier['min'] }} a {{ $tier['max'] }} Colaboradores
                        @endif
                    </h2>
                    <div class="flex flex-col">
                        <span class="flex items-center font-bold text-high text-xl lg:text-2xl gap-1">
                            R$ {{ number_format($tier['price'], 2, ',', '.') }}
                            <span class="text-medium font-medium text-xs">/colaborador</span>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex items-center justify-center w-full mt-8">
        <x-button class="rounded-xl">
            Solicitar proposta
        </x-button>
    </div>
</div>
