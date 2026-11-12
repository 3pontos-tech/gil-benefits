@php
    $legalName = config('services.contact.legal_name');
    $cnpj = config('services.contact.cnpj');
    $controller = filled($legalName) ? $legalName : 'empresa responsável pelo Flamma';
    $controller .= filled($cnpj) ? ', CNPJ ' . $cnpj : '';
    $contactEmail = config('services.contact.email');
@endphp

<x-layouts.site title="Política de Privacidade" description="Como o Flamma coleta, usa, compartilha e protege os seus dados pessoais.">
    <article class="mx-auto flex max-w-3xl flex-col gap-6 px-5 pt-32 pb-24 leading-[1.6] sm:px-8 lg:pt-40">
        <h1 class="text-3xl font-bold lg:text-4xl">Política de Privacidade</h1>

        <p>
            O Flamma oferece consultoria financeira individual, contratada por você ou pela empresa onde você trabalha
            como benefício. Ele é um produto da {{ $controller }}, que é a controladora dos seus dados pessoais nos
            termos da Lei Geral de Proteção de Dados (Lei nº 13.709/2018).
        </p>

        <h2 class="text-xl font-bold">Quais dados coletamos</h2>
        <ul class="flex list-disc flex-col gap-2 pl-6">
            <li>Dados de cadastro: nome, e-mail, telefone, CPF, documento de identidade e a empresa a que você está vinculado.</li>
            <li>Respostas da anamnese financeira e as informações que você decide compartilhar com o consultor.</li>
            <li>Agendamentos, registros e avaliações das sessões, e documentos trocados com o consultor.</li>
            <li>Dados de assinatura, compra de créditos e uso de vouchers.</li>
            <li>Chamados de suporte.</li>
            <li>
                Dados de navegação no site e na área do colaborador: páginas visitadas, origem da visita (como o anúncio
                ou o convite que trouxe você), tipo de navegador e dispositivo, e localização aproximada (cidade e país).
            </li>
        </ul>

        <h2 class="text-xl font-bold">Para que usamos</h2>
        <ul class="flex list-disc flex-col gap-2 pl-6">
            <li>Prestar a consultoria: criar a sua conta, agendar as sessões e conectar você ao consultor.</li>
            <li>Cobrar assinaturas e créditos, e administrar o benefício contratado pela sua empresa.</li>
            <li>Atender pedidos de suporte e mandar avisos sobre a sua conta e as suas sessões.</li>
            <li>Entender como as pessoas chegam ao Flamma e usam o site, para melhorar o serviço e as campanhas.</li>
            <li>Evitar fraudes e cumprir obrigações legais.</li>
        </ul>
        <p>
            Tratamos esses dados para executar o contrato com você ou com a sua empresa, para cumprir obrigações legais e,
            no caso dos dados de navegação, com base no nosso legítimo interesse em medir e melhorar o serviço. Não
            vendemos seus dados.
        </p>

        <h2 class="text-xl font-bold">O que a sua empresa vê</h2>
        <p>
            Quando o Flamma é um benefício da sua empresa, ela acompanha dados de uso do benefício, como a quantidade de
            sessões. O que você conversa com o consultor e as suas respostas financeiras ficam só entre você e o
            consultor.
        </p>

        <h2 class="text-xl font-bold">Com quem compartilhamos</h2>
        <ul class="flex list-disc flex-col gap-2 pl-6">
            <li>Consultores financeiros do Flamma, que atendem você.</li>
            <li>Processadores de pagamento, que cobram assinaturas e créditos.</li>
            <li>Ferramentas de agenda, atendimento e gestão de clientes, que organizam sessões e contatos.</li>
            <li>Provedores de hospedagem, armazenamento de arquivos e envio de e-mail, que mantêm o serviço no ar.</li>
            <li>PostHog, ferramenta de análise de uso que recebe os dados de navegação descritos acima.</li>
            <li>Autoridades públicas, quando a lei exigir.</li>
        </ul>
        <p>
            Esses parceiros tratam os dados apenas para prestar o serviço ao Flamma. Alguns deles, como o PostHog,
            armazenam dados nos Estados Unidos. Essas transferências internacionais seguem as salvaguardas previstas na
            LGPD.
        </p>

        <h2 class="text-xl font-bold">Cookies e tecnologias parecidas</h2>
        <ul class="flex list-disc flex-col gap-2 pl-6">
            <li>Cookies necessários, que mantêm você conectado e protegem os formulários.</li>
            <li>
                Um cookie de atribuição, que guarda por até 30 dias a origem da sua visita (como o anúncio ou o convite
                que trouxe você) até o cadastro.
            </li>
            <li>
                Cookies e armazenamento local do PostHog, que medem o uso do site. Não guardamos o endereço IP: ele é
                usado só para estimar cidade e país e depois descartado. Na área do colaborador, medimos apenas as páginas
                visitadas, sem registrar cliques nem gravar a tela.
            </li>
        </ul>
        <p>Você pode apagar ou bloquear cookies nas configurações do seu navegador.</p>

        <h2 class="text-xl font-bold">Por quanto tempo guardamos</h2>
        <p>
            Guardamos seus dados enquanto a sua conta existir ou o contrato do benefício estiver em vigor, e depois pelos
            prazos que a lei exige, por exemplo para fins fiscais.
        </p>

        <h2 class="text-xl font-bold">Seus direitos</h2>
        <p>
            Você pode pedir a confirmação de que tratamos seus dados, o acesso a eles, a correção, a portabilidade, a
            anonimização, a exclusão e a revogação do consentimento, quando ele for a base do tratamento. Também pode se
            opor ao uso dos dados de navegação.
        </p>

        <h2 class="text-xl font-bold">Contato</h2>
        <p>
            Para exercer seus direitos ou tirar dúvidas, escreva para
            <a href="mailto:{{ $contactEmail }}" class="underline">{{ $contactEmail }}</a>.
            Endereço: Dr. Cardoso de Mello, 1666, Cj. 92, Vila Olímpia, São Paulo.
        </p>

        <p class="text-sm opacity-70">
            Podemos atualizar esta política. A versão em vigor é sempre a publicada nesta página.
        </p>
    </article>
</x-layouts.site>
