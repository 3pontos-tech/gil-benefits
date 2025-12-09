@props([
    'bg' => 'bg-elevation-02dp',
    'color' => 'primary'
])

@php
    [
        $baseClasses,
        $buttonText
    ] = match ($color) {
        'primary' => [
            'bg-gradient-to-br from-brand-primary to-brand-secondary text-light',
            'text-light',
        ],
    }
@endphp

<footer class="{{ $baseClasses }} py-8 sm:py-12 lg:py-16">
    <div class="mx-auto container px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-5 gap-8 sm:gap-10 lg:gap-12 animate-fade-in items-start">
            <div class="col-span-1">
                <h4 class="text-lg sm:text-xl font-semibold  mb-3 sm:mb-4">Links de Serviços</h4>
                <ul class="space-y-1 sm:space-y-2  text-sm sm:text-base">
                    <li>
                        <a href="#home" class="hover:text-secondary transition">
                            Inicio
                        </a>
                    </li>
                    <li>
                        <a href="#how-it-works" class="hover:text-secondary transition">
                            Como Funciona
                        </a>
                    </li>
                    <li>
                        <a href="#challenge" class="hover:text-secondary transition">
                            Nosso Desafio
                        </a>
                    </li>
                    <li>
                        <a href="#assessment" class="hover:text-secondary transition">
                            Consultoria
                        </a>
                    </li>
                    <li>
                        <a href="#pricing" class="hover:text-secondary transition">
                            Preços
                        </a>
                    </li>
                    <li>
                        <a href="#faq" class="hover:text-secondary transition">
                            FAQ
                        </a>
                    </li>
                </ul>
            </div>



            <div class="flex flex-col gap-y-3 sm:gap-y-4 col-span-1">
                <h4 class="text-lg sm:text-xl font-semibold ">Contato e endereço</h4>
                <p class="font-medium  text-sm sm:text-base">contato@firece.com.br</p>
                <div class="flex items-center gap-2">
                    <img src="{{ asset('img/brasil-flag.webp') }}" alt="Phone"
                         class="w-6 sm:w-7 h-4 sm:h-5 object-contain rounded-sm">
                    <p class=" text-sm sm:text-base">(11) 98720-1303</p>
                </div>
            </div>

            <div class="flex flex-col gap-y-3 sm:gap-y-4 col-span-1 lg:col-span-2 xl:col-span-3">
                <h4 class="text-lg sm:text-xl font-semibold ">Nossa Newsletter</h4>
                <p class=" text-sm sm:text-base">
                    Envie nos o seu email e receba as melhores notícias e textos sobre o que
                    acontece no mercado financeiro
                </p>
                <form class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-4">
                    <input type="email" placeholder="Seu email" id="email"
                           class="flex-1 px-3 sm:px-4 py-2 sm:py-2.5 border border-outline-white rounded-lg focus:border-primary focus:outline-none  bg-transparent placeholder: text-sm sm:text-base">
                    <x-button class="w-fit!" variant="white">
                        Inscrever-se
                    </x-button>
                </form>
            </div>
        </div>

        <hr />

        <div class="grid col-span-1 lg:col-span-2 xl:col-span-2 gap-4">
            <a href="/" class="flex flex-col">
                <div class="flex items-center space-x-2">
                    <div class="flex items-center gap-3">
                        <x-logo class="w-36 h-fit" />
                    </div>
                </div>
            </a>
            <div class="flex flex-col gap-y-2 sm:gap-y-3">
                <h3 class=" text-lg sm:text-xl font-semibold">Nosso Endereço</h3>
                <p class=" font-medium text-sm sm:text-base">Dr. Cardoso de Mello, 1666, Cj, 92 Vila Olímpia, São Paulo
                </p>
            </div>
        </div>
    </div>
</footer>
