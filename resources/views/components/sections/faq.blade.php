@props([
     'description',
     'solutions' => collect(),
])

@php
    $title = 'Perguntas frequentes';
    $keywords = ['educação', 'financeira', 'benefício', 'corporativo'];
    $description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. In efficitur velit vitae enim sodales
        sodales. Donec lectus nisi, aliquam eu ante at, blandit laoreet ligula. ';

    $solutions = [
        [
            'question' => 'Como funciona o processo de atendimento?',
            'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla facilisi. Phasellus sit amet erat ut turpis dictum dignissim.',
        ],
        [
            'question' => 'Posso alterar minhas informações cadastrais?',
            'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec pretium velit ut dignissim commodo. Integer a massa vel arcu faucibus vehicula.',
        ],
        [
            'question' => 'Quais são as formas de pagamento aceitas?',
            'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean porttitor, odio in suscipit volutpat, lacus metus fermentum orci, sed lacinia arcu urna ac ex.',
        ],
        [
            'question' => 'Existe um prazo de cancelamento?',
            'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent non tristique turpis. Aliquam erat volutpat. Suspendisse id risus eros.',
        ],
        [
            'question' => 'Como posso entrar em contato com o suporte?',
            'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur posuere mi id malesuada feugiat. Nulla facilisi. Pellentesque habitant morbi tristique.',
        ],
    ];
@endphp

<section class="mx-auto mb-28 sm:mb-44 w-full grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div>
        <x-headline class="lg:order-1 max-w-full!" align="left" :keywords="$keywords">
            <x-slot:title>
                {{ $title }}
            </x-slot:title>
            <x-slot:description>
                {{ $description }}
            </x-slot:description>
            <x-slot:actions>
                <x-button>
                    Saiba mais
                </x-button>
            </x-slot:actions>
        </x-headline>
    </div>
    <div class="flex flex-col rounded-xl transition items-stretch">
        <div class="w-full space-y-4 sm:space-y-6 md:space-y-8">
            @forelse($solutions as $index => $solution)
                <div
                    x-data="{ open: false }"
                    :class="{'bg-gradient-to-br from-brand-primary to-brand-secondary': open}"
                    class="group transition-all duration-300 ease-in-out bg-elevation-01dp border border-outline-light
                        hover:border-brand-primary rounded-lg p-3 sm:p-4"
                >
                    <h3 class="flex">
                        <button type="button" @click="open = !open" :aria-expanded="open"
                                :class="{'text-light': open, 'text-medium': !open,}"
                                class="flex flex-1 items-center justify-between font-medium transition-all
                                    text-left text-base sm:text-lg"
                                aria-controls="faq-content-{{ $index }}">
                            {{ $solution['question'] ?? '' }}
                            <svg
                                class="h-4 w-4 sm:h-5 sm:w-5 text-muted transition-transform duration-200 text-brand-primary flex-shrink-0 ml-4"
                                :class="{ 'rotate-180 text-light': open }" stroke="currentColor"
                                fill="none"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </h3>
                    <div x-show="open" x-collapse id="faq-content-{{ $index }}">
                        <div
                            class="text-light bg-base-100 pt-3 text-sm sm:text-base leading-relaxed">
                            {{ $solution['answer'] ?? '' }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-6 sm:py-8">
                    <p class="text-muted text-sm sm:text-base">Nenhum item cadastrado.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
