@php
    $title = 'Como funciona?';
    $description = 'A empresa contrata um pacote de horas de atendimento (mensal, semestral ou anual). Os colaboradores
        agendam seus atendimentos diretamente pela plataforma Flamma. Você tem acesso à atendimentos individuais com
        até 60 minutos, online ou presenciais.  Relatórios consolidados de uso para acompanhamento da adesão e
        impacto.';
@endphp

<section class="flex flex-col mx-auto mb-28 sm:mb-44 space-y-8">
    <x-headline class="max-w-full!" align="left" contentLayout="grid grid-cols-1 lg:grid-cols-[1fr_2fr] gap-4 lg:gap-12">
        <x-slot:title>
            {{ $title }}
        </x-slot:title>
        <x-slot:description>
            {{ $description }}
        </x-slot:description>
    </x-headline>
    <x-partials.card-grid>
        <x-card>
            <x-slot:icon>
                <x-badge>
                    01
                </x-badge>
            </x-slot:icon>
            <x-slot:title>
                Lorem Ipsum
            </x-slot:title>
            <x-slot:description>
                A empresa contrata um pacote de horas de atendimento (mensal, semestral ou anual).
            </x-slot:description>
        </x-card>
    </x-partials.card-grid>
</section>
