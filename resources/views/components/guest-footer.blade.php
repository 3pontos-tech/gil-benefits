{{--
    Rodapé do site (e do /help-center, que o painel guest injeta pelo mesmo componente).

    Medidas do Figma, em px de 1920: gutter de 122 (o mesmo das seções), quatro colunas
    com passo de ~393 e a última mais larga para o formulário, títulos de coluna em 16
    bold, itens em 14 com 40 de passo (o que dá 20 de vão, porque o título e os itens já
    ocupam 24 e 21 de linha), campo de 301 e botão de 142. A divisória separa as colunas
    do bloco de marca — logo de 135, "Nosso Endereço" e o endereço.
--}}
<footer class="bg-orange-primary py-12 text-light lg:py-16">
    <div class="mx-auto flex max-w-[1800px] flex-col gap-10 px-5 sm:px-8 lg:gap-[35px] lg:px-[62px]">
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1.3fr] lg:gap-10">
            <div class="flex flex-col gap-5">
                <h4 class="text-base font-bold leading-[1.5]">Links e Serviços</h4>

                <ul class="flex flex-col gap-5 text-sm">
                    <li><a href="{{ route('site.home') }}" class="transition hover:opacity-80">Início</a></li>
                    <li><a href="{{ route('site.companies') }}" class="transition hover:opacity-80">Para Empresas</a></li>
                    <li><a href="{{ route('site.collaborator') }}" class="transition hover:opacity-80">Para Você</a></li>
                    <li><a href="{{ route('site.home') }}#how-it-works" class="transition hover:opacity-80">Como Funciona</a></li>
                    <li><a href="{{ route('site.home') }}#challenge" class="transition hover:opacity-80">Nosso Desafio</a></li>
                </ul>
            </div>

            <div class="flex flex-col gap-5">
                <h4 class="text-base font-bold leading-[1.5]">Ajuda e Conteúdo</h4>

                <ul class="flex flex-col gap-5 text-sm">
                    {{-- A âncora #assessment saiu da home na reconstrução; a seção equivalente é esta. --}}
                    <li><a href="{{ route('site.home') }}#por-que-confiar" class="transition hover:opacity-80">Por que confiar</a></li>
                    <li><a href="{{ route('site.home') }}#pricing" class="transition hover:opacity-80">Preços</a></li>
                    <li><a href="{{ route('site.home') }}#faq" class="transition hover:opacity-80">FAQ</a></li>
                    <li>
                        <a href="{{ \App\Filament\Guest\Pages\HelpCenterPage::getUrl(panel: 'guest') }}"
                           class="transition hover:opacity-80">Abrir Chamado</a>
                    </li>
                </ul>
            </div>

            <div class="flex flex-col gap-5">
                <h4 class="text-base font-bold leading-[1.5]">Contato e Endereço</h4>

                <div class="flex flex-col gap-5 text-sm">
                    <a href="mailto:contato@firece.com.br" class="transition hover:opacity-80">contato@firece.com.br</a>

                    <div class="flex items-center gap-2">
                        <img src="{{ asset('img/brasil-flag.webp') }}" alt="Brasil"
                             class="h-4 w-6 shrink-0 rounded-sm object-contain">
                        <a href="tel:+5511987201303" class="transition hover:opacity-80">(11) 98720-1303</a>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-[min(1.6667vw,32px)]">
                <h4 class="text-base font-bold leading-[1.5]">Nossa Newsletter</h4>

                <p class="text-sm leading-[1.5]">
                    Envie nos o seu email e receba as melhores notícias e textos sobre o que
                    acontece no mercado financeiro
                </p>

                <form class="flex flex-col items-stretch gap-4 sm:flex-row sm:items-center">
                    <label for="newsletter-email" class="sr-only">Seu email</label>

                    <input
                        type="email"
                        id="newsletter-email"
                        placeholder="Digite seu email"
                        class="w-full rounded-md border border-white/60 bg-transparent px-4 py-2.5 text-sm placeholder:text-light/70 focus:border-white focus:outline-none sm:max-w-[301px]"
                    >

                    <x-button class="w-full! shrink-0 sm:w-[142px]!" variant="white" size="md">
                        Inscrever-se
                    </x-button>
                </form>
            </div>
        </div>

        <hr class="border-white/30">

        <div class="flex flex-col gap-5">
            <a href="{{ route('site.home') }}" class="w-fit">
                <x-logo class="w-[135px]" />
            </a>

            <div class="flex flex-col gap-5">
                <h4 class="text-base font-bold leading-[1.5]">Nosso Endereço</h4>

                <p class="text-sm leading-[1.5]">
                    Dr. Cardoso de Mello, 1666, Cj, 92 Vila Olímpia, São Paulo
                </p>
            </div>
        </div>
    </div>
</footer>
