<x-email.layout title="Solicitação recebida" :preheader="'Protocolo ' . $ticket->protocol . ': recebemos a sua solicitação.'" closing="Se precisar de mais informações, responda este e-mail informando o número do protocolo." :signature="__('emails.layout.support_signature', ['app' => config('app.name')])">
    <x-email.heading :lines="[
        ['Olá, ' . ($requesterName ?? 'usuário') . '!', 'light'],
        ['Solicitação', 'bold'],
        ['recebida.', 'bold'],
    ]" />

    <x-email.text size="lg">Recebemos a sua solicitação e ela foi encaminhada para a área responsável.</x-email.text>

    <x-email.details>
        <x-email.detail label="Protocolo">{{ $ticket->protocol }}</x-email.detail>
        <x-email.detail label="Assunto">{{ $ticket->subject }}</x-email.detail>
        <x-email.detail label="Área">{{ $channelName }}</x-email.detail>
    </x-email.details>

    <x-email.text :top="12">Você pode acompanhar o andamento em <strong style="color:#2B2B2B;">Meus Chamados</strong>, na plataforma.</x-email.text>
</x-email.layout>
