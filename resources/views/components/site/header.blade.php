{{--
    Topo do site institucional.

    Logo à esquerda, os links das três páginas do site no centro, botão de acesso à
    direita e, abaixo de lg, a gaveta com os mesmos itens. Os links vivem aqui, no
    array $items; o painel guest (que ainda serve o /help-center) tem a própria cópia
    em GuestPanelProvider::navigationItems().
--}}

@php
    $home = route('site.home');

    // Só as três páginas do site. As âncoras das seções da home saíram do menu — elas
    // continuam existindo nas próprias seções e nos links do rodapé.
    $items = [
        ['label' => 'Inicio', 'url' => $home, 'active' => request()->routeIs('site.home')],
        ['label' => 'Para Empresas', 'url' => route('site.companies'), 'active' => request()->routeIs('site.companies')],
        ['label' => 'Para Você', 'url' => route('site.collaborator'), 'active' => request()->routeIs('site.collaborator')],
    ];

    $user = auth()->user();

    $userLinks = $user === null ? [] : array_values(array_filter([
        $user->hasAnyRole(['super_admin', 'admin', 'employee'])
            ? ['label' => 'Acessar Plataforma', 'url' => '/app']
            : null,
        $user->hasAnyRole(['super_admin', 'admin'])
            ? ['label' => 'Painel Administrativo', 'url' => '/admin']
            : null,
        $user->hasAnyRole(['super_admin', 'admin', 'company_owner'])
            ? ['label' => 'Administrativo da Empresa', 'url' => '/company']
            : null,
    ]));
@endphp

<div class="site-topbar-ctn" x-data="{ mobileMenu: false }">
    <nav class="site-topbar">
        {{-- Abre a gaveta abaixo de lg, no lugar do botão que o painel colocava aqui --}}
        <button
            type="button"
            class="site-mobile-menu-toggle"
            x-on:click="mobileMenu = true"
            aria-label="Abrir menu"
        >
            <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
            </svg>
        </button>

        <div class="site-topbar-start">
            <a href="{{ $home }}">
                <x-logo color="white" class="h-6 w-auto" />
            </a>
        </div>

        <ul class="site-topbar-nav">
            @foreach ($items as $item)
                <li @class(['site-topbar-item', 'is-active' => $item['active'] ?? false])>
                    <a href="{{ $item['url'] }}" class="site-topbar-item-btn">
                        <span class="site-topbar-item-label">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="site-topbar-end">
            @auth
                {{-- Equivalente ao user menu do painel: os mesmos destinos por papel, mais o logout --}}
                <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false"
                     x-on:keydown.escape.window="open = false">
                    <button type="button" class="flex items-center gap-2 rounded-full" x-on:click="open = ! open"
                            :aria-expanded="open" aria-label="Conta">
                        @if ($avatar = $user->getFilamentAvatarUrl())
                            <img src="{{ $avatar }}" alt="{{ $user->name }}" class="size-8 rounded-full object-cover">
                        @else
                            <span class="flex size-8 items-center justify-center rounded-full bg-white text-sm font-medium text-brand-primary">
                                {{ str($user->name)->substr(0, 1)->upper() }}
                            </span>
                        @endif
                    </button>

                    <div
                        x-cloak
                        x-show="open"
                        x-transition.opacity
                        class="absolute end-0 top-full z-20 mt-2 w-screen max-w-[14rem] rounded-lg bg-white p-1 shadow-lg ring-1 ring-black/5"
                    >
                        @foreach ($userLinks as $link)
                            <a href="{{ $link['url'] }}"
                               class="flex w-full items-center gap-2 rounded-md p-2 text-sm hover:bg-[var(--site-nav-hover-bg)]">
                                <span class="text-sm font-medium text-[var(--site-nav-text)]">{{ $link['label'] }}</span>
                            </a>
                        @endforeach

                        <form method="POST" action="{{ route('filament.guest.auth.logout') }}">
                            @csrf
                            <button type="submit"
                                    class="flex w-full items-center gap-2 rounded-md p-2 text-sm hover:bg-[var(--site-nav-hover-bg)]">
                                <span class="text-sm font-medium text-[var(--site-nav-text)]">Sair</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>

        @guest
            <x-button class="w-fit!" variant="white" href="/app/login">Acesso Colaborador</x-button>
        @endguest
    </nav>

    {{-- Gaveta mobile --}}
    <div x-cloak x-show="mobileMenu" x-transition.opacity.300ms class="site-mobile-menu-overlay"
         x-on:click="mobileMenu = false"></div>

    <aside
        x-cloak
        x-show="mobileMenu"
        x-transition:enter="transition duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:leave="transition duration-300"
        x-transition:leave-end="-translate-x-full"
        class="site-mobile-menu"
    >
        <div class="site-mobile-menu-header">
            <a href="{{ $home }}" x-on:click="mobileMenu = false">
                <x-logo color="dark" class="h-6 w-auto" />
            </a>

            <button type="button" class="site-mobile-menu-toggle ms-auto" x-on:click="mobileMenu = false"
                    aria-label="Fechar menu">
                <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                     stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="site-mobile-menu-nav">
            @foreach ($items as $item)
                <a href="{{ $item['url'] }}"
                   x-on:click="mobileMenu = false"
                   @class(['site-mobile-menu-item', 'is-active' => $item['active'] ?? false])>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </aside>
</div>
