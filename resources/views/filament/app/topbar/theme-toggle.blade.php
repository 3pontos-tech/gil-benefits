{{--
    Alternador de tema da topbar. Um ícone só, como no design: lua no tema claro
    (leva ao escuro) e sol no escuro (leva ao claro).

    Usa a mesma infraestrutura do theme-switcher nativo — o evento theme-changed
    é tratado pelo dark-mode.js do Filament, que persiste no localStorage e
    resolve $store.theme. O botão de três estados do pacote não serve aqui: ele
    chama close() do dropdown do menu de usuário e quebraria fora dele.
--}}
<x-filament::icon-button
    color="gray"
    icon="heroicon-o-moon"
    icon-size="lg"
    :label="__('filament-panels::layout.actions.theme_switcher.dark.label')"
    x-cloak
    x-data="{}"
    x-on:click="$dispatch('theme-changed', 'dark')"
    x-show="$store.theme === 'light'"
/>

<x-filament::icon-button
    color="gray"
    icon="heroicon-o-sun"
    icon-size="lg"
    :label="__('filament-panels::layout.actions.theme_switcher.light.label')"
    x-cloak
    x-data="{}"
    x-on:click="$dispatch('theme-changed', 'light')"
    x-show="$store.theme === 'dark'"
/>
