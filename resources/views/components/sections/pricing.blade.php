@php
    $title = 'Conheça nosso plano empresarial';
    $keywords = ['plano', 'empresarial'];
    $description = 'Entenda como funciona para contratar o Flamma e se houver alguma dúvida, entre em contato.  Faça a diferença na vida dos seus colaboradores e na sua empresa.';
@endphp

<section class="flex flex-col mx-auto mb-28 sm:mb-44 items-center justify-center space-y-8 lg:space-y-16 w-full scroll-mt-28" id="pricing">
    <x-headline class="max-w-3xl!">
        <x-slot:title>
            {{ $title }}
        </x-slot:title>
        <x-slot:description>
            {{ $description }}
        </x-slot:description>
    </x-headline>

    <livewire:pricing-calculator />
</section>
