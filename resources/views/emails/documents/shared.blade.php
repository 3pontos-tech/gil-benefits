@use('App\Support\Mail\MailText')
<x-email.layout title="Novo documento compartilhado" preheader="Tem um documento novo esperando por você na plataforma." :closing="__('emails.layout.reply')">
    <x-email.heading :lines="[
        ['Olá, ' . $employeeName . '!', 'light'],
        ['Novo documento', 'bold'],
        ['para você.', 'bold'],
    ]" />

    @if ($consultantName)
        <x-email.text size="lg">{{ MailText::inline('O(a) consultor(a) **' . $consultantName . '** compartilhou um documento com você.') }}</x-email.text>
    @else
        <x-email.text size="lg">Um novo documento foi compartilhado com você.</x-email.text>
    @endif

    <x-email.details>
        <x-email.detail label="Documento">{{ $documentTitle }}</x-email.detail>
    </x-email.details>

    <x-email.text :top="12">Para ver o documento, acesse a plataforma.</x-email.text>
    <x-email.button :url="$panelUrl">Ver documento</x-email.button>
</x-email.layout>
