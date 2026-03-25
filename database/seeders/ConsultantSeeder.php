<?php

namespace Database\Seeders;

use App\Enums\AvailableTagsEnum;
use Illuminate\Database\Seeder;
use TresPontosTech\Consultants\Models\Consultant;

class ConsultantSeeder extends Seeder
{
    private array $consultants = [
        [
            'name' => 'Daniel Reis',
            'slug' => 'danielhe4rt',
            'phone' => '+551112312312',
            'email' => 'hey@danielheart.dev',
            'short_description' => 'Um não consultor com zero anos de proficiência',
            'biography' => <<<MARKDOWN
            Meu nome é Daniel Reis, também conhecido como DanielHe4rt. Atualmente moro em São Paulo, Brasil, e meu objetivo é ajudar o máximo de desenvolvedores a alcançarem seus objetivos o mais rápido possível.

            Fundador da [He4rt Developers](https://github.com/he4rt) em 2018, uma comunidade focada em apoiar desenvolvedores juniores em seus primeiros passos. Durante 5 anos liderei e aprendi sobre gestão de comunidades por lá, e agora meu próximo passo é criar uma comunidade global (em breve).

            Também faço transmissões de [LiveCoding na Twitch.tv](https://twitch.tv/danielhe4rt) quase diariamente desde 2018, aprendendo conceitos e ensinando ao mesmo tempo, elevando o conceito de "Aprender em Público" a um novo nível. A partir disso, comecei a escrever artigos no [Dev.to](https://dev.to/danielhe4rt) e criar vídeos no [YouTube](https://youtube.com/danielhe4rt), o que foi um divisor de águas para minha carreira como desenvolvedor.

            ### Curiosidades sobre mim

            * Fiz aulas de música por 10 anos, tocando violão clássico, violino, violoncelo, baixo e cantando em um coral que era referência no estado de São Paulo na época. Minha apresentação final nesse caminho foi na ["Sala São Paulo"](https://en.wikipedia.org/wiki/Sala_S%C3%A3o_Paulo), que é o **maior e mais importante** teatro do Brasil;
            * Comecei a programar em 2011 usando **Pawn Lang**, testando linhas de código no estilo “tentativa/erro” em um gamemode de **GTA San Andreas**, apenas para ver se algo mudava no jogo;
            * Tive um “sensei” que me ensinou muito sobre programação e com esse conhecimento avancei bastante. Hoje faço o mesmo que ele fez por mim: ajudo qualquer pessoa que me peça suporte;
            * Já tive 2 servidores privados de MapleStory (Java), 3 servidores privados de GTA SA\:MP (Pawn) e 1 servidor de Minecraft (Java);
            * Gosto muito de ajudar pessoas e aprender coisas aleatórias.
            MARKDOWN,
            'readme' => <<<'MARKDOWN'
             Se você tem interesse em ter uma consultoria comigo, se liga em como chegar sem ter muitos problemas.

            **Preparativos para reunião com freelance de software:**

            * Defina claramente o objetivo do projeto.
            * Liste as principais funcionalidades desejadas.
            * Tenha referências de design ou sistemas semelhantes.
            * Estabeleça orçamento e prazo estimados.
            * Reúna informações técnicas já disponíveis (domínio, hospedagem, integrações).
            * Prepare perguntas sobre manutenção e suporte.
            * Priorize o que é essencial vs. desejável.

            Quer que eu transforme isso em um checklist em Markdown pronto para enviar ao cliente?
            MARKDOWN,
        ],
        [
            'name' => 'Mariana Costa',
            'slug' => 'marianacosta',
            'phone' => '+5511998765432',
            'email' => 'mariana.costa@financeinsights.com',
            'short_description' => 'Analista de investimentos com foco em renda variável e valuation de empresas',
            'biography' => <<<'MARKDOWN'
            Meu nome é Mariana Costa, sou analista de investimentos há mais de 8 anos, especializada em **renda variável, valuation de empresas e análise setorial**.

            Ao longo da minha carreira, trabalhei em corretoras independentes, em bancos de investimento e como consultora autônoma, sempre com foco em conectar teoria financeira a estratégias práticas. Minha missão é ajudar investidores a entender o mercado de ações de forma simples e fundamentada, sem cair em modismos.

            ### Curiosidades sobre mim
            * Já analisei mais de 200 empresas brasileiras e estrangeiras para relatórios de recomendação;
            * Sou certificada **CNPI** e apaixonada por ensinar conceitos de valuation;
            * Tenho uma coluna mensal em um portal de economia comentando resultados trimestrais de grandes companhias;
            * No tempo livre, gosto de correr maratonas e usar a disciplina dos treinos como paralelo ao mundo dos investimentos.
            MARKDOWN,
            'readme' => <<<'MARKDOWN'
            # Como trabalhar comigo?

            Gosto de objetividade: se tiver dúvidas sobre valuation, fundamentos de uma empresa ou tese de investimento, traga os dados que já possui.

            Minhas contribuições incluem:
            - Relatórios de análise fundamentalista
            - Consultoria para carteiras de longo prazo
            - Workshops e mentorias sobre valuation
            MARKDOWN,
        ],
        [
            'name' => 'Ricardo Almeida',
            'slug' => 'ricardoalmeida',
            'phone' => '+5511988880000',
            'email' => 'ricardo.almeida@quantstrat.com',
            'short_description' => 'Especialista em estratégias quantitativas e trading algorítmico',
            'biography' => <<<'MARKDOWN'
            Meu nome é Ricardo Almeida, sou gestor quantitativo e desenvolvedor de **estratégias algorítmicas** no mercado financeiro há mais de 12 anos.

            Minha trajetória começou como programador, e acabei unindo a paixão por tecnologia com o universo das finanças, desenvolvendo **robôs de negociação, backtests e modelos estatísticos** que ajudam a operar com disciplina e sem vieses emocionais.

            ### Curiosidades sobre mim
            * Criei meus primeiros algoritmos de trading em **Python** e **R** em 2010, quando esse tema ainda era pouco explorado no Brasil;
            * Trabalhei em fundos quantitativos e hoje atuo de forma independente atendendo traders e gestoras;
            * Sou apaixonado por xadrez, e aplico muitos conceitos estratégicos do jogo em minhas análises de risco;
            * Acredito que transparência e controle são mais importantes que promessas de retorno.
            MARKDOWN,
            'readme' => <<<'MARKDOWN'
            # Como trabalhar comigo?

            Não espere milagres do mercado, mas sim consistência baseada em dados.

            Contribuições típicas:
            - Desenvolvimento de modelos de trading sistemático
            - Estruturação de backtests robustos
            - Treinamento sobre estatística aplicada ao mercado
            MARKDOWN,
        ],
        [
            'name' => 'Fernanda Oliveira',
            'slug' => 'fernandaoliveira',
            'phone' => '+5511977771234',
            'email' => 'fernanda.oliveira@fintechadvisor.com',
            'short_description' => 'Consultora financeira pessoal com foco em planejamento e independência financeira',
            'biography' => <<<'MARKDOWN'
            Meu nome é Fernanda Oliveira e trabalho como consultora financeira há mais de 6 anos.
            Minha missão é **ajudar pessoas comuns a organizarem suas finanças pessoais**, construírem reservas e investirem com segurança para alcançar a independência financeira.

            Diferente do mercado institucional, meu foco sempre foi o investidor pessoa física, especialmente aqueles que querem sair das dívidas e criar um futuro sustentável.

            ### Curiosidades sobre mim
            * Ajudei mais de 500 famílias a estruturarem seus planejamentos financeiros pessoais;
            * Tenho certificação **CEA (ANBIMA)** e formação em coaching financeiro;
            * Costumo fazer paralelos entre finanças pessoais e hábitos de vida saudável;
            * Adoro viajar e mostrar como o planejamento financeiro pode tornar sonhos possíveis.
            MARKDOWN,
            'readme' => <<<'MARKDOWN'
            # Como trabalhar comigo?

            Transparência e empatia são as palavras-chave.

            Contribuições:
            - Planejamento financeiro pessoal detalhado
            - Estratégias de investimentos para iniciantes
            - Workshops de organização financeira
            MARKDOWN,
        ],
    ];

    public function run(): void
    {
        foreach ($this->consultants as $data) {
            $consultantData = $data;
            $consultantData['socials_urls'] = [
                'linkedin' => 'https://www.linkedin.com/in/',
                'instagram' => 'https://www.instagram.com/',
                'facebook' => 'https://www.facebook.com/',
                'twitter' => 'https://www.twitter.com/',
                'youtube' => 'https://www.youtube.com/',
            ];

            $consultant = Consultant::query()->create($consultantData);

            foreach (AvailableTagsEnum::cases() as $case) {
                $consultant->attachTags($case->getDefault(), $case->value);
            }
        }
    }
}
