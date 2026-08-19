{{--
    Simulador de preço da /para-empresas (#simulador).

    Tudo — cabeçalho, controles e CTA — mora dentro de um card claro único. A seção
    tem a massa escura em degraus sangrando por baixo do simulador, e com o cabeçalho
    solto (fora do card) o título em text-high caía sobre o cinza-escuro e ficava
    ilegível. O card também dá ao bloco a mesma largura e o mesmo alinhamento da foto
    logo acima, com a massa aparecendo só nas laterais.

    Tipografia, cores e o CTA seguem o resto do site: título de bloco em 24/32px,
    corpo em 16/20px, vermelho da marca (--brand-primary) nos destaques e no estado
    ativo, e o botão no mesmo padrão dos outros CTAs (chapado, sem raio, xl).
--}}
<div class="flex w-full flex-col gap-8 border border-outline-light bg-elevation-surface p-6 text-medium lg:gap-10 lg:p-12">
    <header class="flex flex-col gap-4">
        <h3 class="text-2xl font-bold leading-[1.5] text-high lg:text-[32px]">Adicionar colaboradores</h3>

        <p class="text-base font-medium leading-[1.5] lg:text-xl lg:font-normal">
            O valor de acesso é cobrado por colaborador — quanto mais funcionários incluídos, mais econômica fica a assinatura.
        </p>
    </header>

    <div
        class="flex flex-col gap-8 lg:gap-10"

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
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
            <div class="order-1 flex flex-col gap-2">
                <label for="seats-count" class="text-base font-medium">Quantos colaboradores?</label>

                <div class="gradient-wrapper max-w-56">
                    <input
                        id="seats-count"
                        x-model.number="sliderValue"
                        type="number"
                        min="1"
                        class="w-full border border-outline-light bg-elevation-01dp px-4 py-2.5 text-2xl font-bold text-high"
                    />
                </div>
            </div>

            <div class="order-3 flex flex-col gap-2 sm:order-2 sm:items-end sm:justify-self-end">
                <span class="text-base font-medium">Custo mensal</span>

                <p class="text-[32px] font-bold leading-[1.2] text-high lg:text-[40px]">
                    R$ <span x-text="totalCost.toLocaleString('pt-BR', {minimumFractionDigits: 2})"></span>
                </p>
            </div>

            <div class="order-2 col-span-full flex flex-col gap-3 sm:order-3">
                <div class="flex justify-between text-sm font-medium">
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
                    aria-label="Quantidade de colaboradores"
                >
            </div>
        </div>

        <div class="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach($planTiers as $tier)
                <div
                    wire:key="tier-{{ $tier['id'] }}"
                    class="flex flex-col gap-3 border bg-elevation-01dp p-4 transition-colors duration-300"
                    :class="activeTierId === {{ $tier['id'] }}
                        ? 'border-brand-primary bg-brand-primary/5'
                        : 'border-outline-light'"
                >
                    <h4 class="text-sm font-medium">
                        @if($loop->last)
                            +{{ $tier['max'] - 1 }} Colaboradores
                        @else
                            {{ $tier['min'] }} a {{ $tier['max'] }} Colaboradores
                        @endif
                    </h4>

                    <p class="flex items-baseline gap-1 text-xl font-bold text-high lg:text-2xl">
                        R$ {{ number_format($tier['price'], 2, ',', '.') }}
                        <span class="text-xs font-medium text-medium">/colaborador</span>
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex w-full items-center justify-center">
        <x-button
            class="w-full! sm:w-fit!"
            variant="flat"
            size="xl"
            rounded="none"
            href="https://wa.me/5511976205711?text=Flamma"
            target="_blank"
            rel="noopener noreferrer"
        >
            Solicitar orçamento
        </x-button>
    </div>
</div>
