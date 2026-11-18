@use('App\Support\Mail\MailText')
<x-email.layout :title="$ticket->protocol" :preheader="'Protocolo ' . $ticket->protocol . ': ' . $ticket->status->getLabel() . '.'" closing="Se precisar de mais informações, responda este e-mail informando o número do protocolo." :signature="__('emails.layout.support_signature', ['app' => config('app.name')])">
    <x-email.heading :lines="[
        ['Olá, ' . ($requesterName ?? 'usuário') . '!', 'light'],
        ['Seu chamado', 'bold'],
        ['foi atualizado.', 'bold'],
    ]" />

    <x-email.text size="lg">{{ MailText::inline(__('support::mail.status_updated.intros.' . $ticket->status->value)) }}</x-email.text>

    <x-email.details>
        <x-email.detail label="Protocolo">{{ $ticket->protocol }}</x-email.detail>
        <x-email.detail label="Assunto">{{ $ticket->subject }}</x-email.detail>
        <x-email.detail label="Status" highlight>{{ $ticket->status->getLabel() }}</x-email.detail>
    </x-email.details>
</x-email.layout>
