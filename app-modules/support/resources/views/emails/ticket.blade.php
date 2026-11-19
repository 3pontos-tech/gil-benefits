@use('App\Support\Mail\MailText')
@php
    $company = $ticket->company?->name ?? $ticket->visitor_company_name;
    $technical = array_filter([
        'URL' => $ticket->url,
        'Navegador' => $ticket->browser,
        'Dispositivo' => $ticket->device,
        'Ambiente' => $ticket->environment,
    ]);
@endphp
<x-email.layout :title="'[' . $ticket->protocol . '] ' . $ticket->subject" :preheader="$ticket->getRequesterName() . ': ' . $ticket->subject">
    <x-email.heading :lines="[
        ['Novo chamado', 'bold'],
        [$ticket->protocol, 'light'],
    ]" />

    <x-email.details>
        <x-email.detail label="Categoria">{{ $ticket->category->getLabel() }}</x-email.detail>
        <x-email.detail label="Área">{{ $channelName }}</x-email.detail>
        <x-email.detail label="Solicitante">{{ $ticket->getRequesterName() ?? '—' }}</x-email.detail>
        <x-email.detail label="E-mail">{{ $ticket->getRequesterEmail() ?? '—' }}</x-email.detail>
        @if (filled($company))
            <x-email.detail label="Empresa">{{ $company }}</x-email.detail>
        @endif
    </x-email.details>

    <x-email.subheading light="Assunto:" :bold="$ticket->subject" :top="28" />
    <x-email.text :top="12">{{ MailText::inline($ticket->description) }}</x-email.text>

    @if ($technical !== [])
        <x-email.infobox bold="Dados" light="técnicos" :top="28">
            @foreach ($technical as $label => $value)
                <x-email.note :label="$label . ':'">{{ $value }}</x-email.note>
            @endforeach
        </x-email.infobox>
    @endif

    <x-email.text size="sm" :top="16">Aberto em {{ $ticket->created_at->format('d/m/Y H:i') }}</x-email.text>
</x-email.layout>
