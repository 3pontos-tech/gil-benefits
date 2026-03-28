@props([
     'description',
     'solutions' => collect(),
])

@php
    $title = 'Perguntas frequentes';
    $keywords = ['educação', 'financeira', 'benefício', 'corporativo'];
    $description = 'Se houver alguma dúvida, nossas perguntas e respostas podem ajudar. Se não achar o que precisa, pode entrar em contato. ';

    $solutions = [
        [
            'question' => 'Como funciona o processo de atendimento?',
            'answer' => 'O processo de atendimento é simples e focado na sua conveniência.
            O usuário acessa o painel exclusivo da Flamma, verifica a disponibilidade dos especialistas e
            agenda sua sessão de 1 hora no horário que for mais adequado. O atendimento é realizado online,
            garantindo flexibilidade e privacidade. Se o acesso for via benefício corporativo, o RH gerencia
            o uso através de um painel administrativo.',
        ],
        [
            'question' => 'Posso alterar minhas informações cadastrais?',
            'answer' => 'Sim, você pode alterar suas informações cadastrais diretamente no painel do usuário da Flamma.
            Caso encontre alguma dificuldade ou precise alterar dados críticos, como o e-mail de acesso, deve entrar
            em contato com o suporte técnico da Flamma.',
        ],
        [
            'question' => 'Quais são as formas de pagamento aceitas?',
            'answer' => 'As formas de pagamento aceitas variam de acordo com o tipo de contratação (individual ou corporativa).
            Para contratações individuais, aceitamos [Mencionar formas de pagamento, ex: cartão de crédito, boleto].
            Para contratações corporativas (benefício para funcionários), o pagamento é realizado pela empresa contratante,
            geralmente por meio de faturamento mensal ou anual, conforme o contrato estabelecido. O usuário final do
            benefício não realiza nenhum pagamento pelas sessões.',
        ],
        [
            'question' => 'Existe um prazo de cancelamento?',
            'answer' => 'O prazo de cancelamento do benefício é regido pelo contrato de prestação de serviços firmado
            entre a Flamma e a sua empresa. Para informações detalhadas sobre o prazo e as condições de cancelamento,
            o RH deve consultar o contrato ou entrar em contato com o nosso time comercial.',
        ],
        [
            'question' => 'Como posso entrar em contato com o suporte?',
            'answer' => 'O suporte técnico e operacional da Flamma pode ser contatado através dos seguintes canais:<br><br>
                <strong>Para Colaboradores:</strong> Através do chat de suporte disponível no painel do usuário ou pelo e-mail de suporte (a ser fornecido pela Flamma).<br><br>
                <strong>Para o RH/Gestores:</strong> Através do canal de atendimento exclusivo para clientes corporativos (e-mail ou telefone dedicados, a serem fornecidos no momento da contratação).',
        ],
    ];
@endphp

<section class="mx-auto mb-28 sm:mb-44 w-full grid grid-cols-1 lg:grid-cols-2 gap-8 scroll-mt-28" id="faq">
    <div>
        <x-headline class="lg:order-1 max-w-full!" align="left" :keywords="$keywords">
            <x-slot:title>
                {{ $title }}
            </x-slot:title>
            <x-slot:description>
                {{ $description }}
            </x-slot:description>
            <x-slot:actions>
                <x-button rel="noopener noreferrer" target="_blank" href="https://wa.me/5511976205711?text=Flamma">
                    Entre em contato
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
                        <div class="text-light bg-base-100 pt-3 text-sm sm:text-base leading-relaxed">
                            {!! $solution['answer'] ?? '' !!}
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
