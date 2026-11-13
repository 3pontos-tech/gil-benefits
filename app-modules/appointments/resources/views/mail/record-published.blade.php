@use('App\Support\Mail\MailText')
@php($consultant = $record->appointment->consultant->name ?? 'seu consultor')
<x-email.layout title="Sua ata está disponível" preheader="A ata do seu atendimento já está disponível na plataforma." :closing="__('emails.layout.reply')">
    <x-email.heading :lines="[
        ['Olá, ' . $record->appointment->user->name . '!', 'light'],
        ['Sua ata', 'bold'],
        ['está pronta.', 'bold'],
    ]" />

    <x-email.text size="lg">{{ MailText::inline('A ata do seu atendimento de **' . $record->appointment->appointment_at->format('d/m/Y') . '** com **' . $consultant . '** já está disponível na plataforma.') }}</x-email.text>
    <x-email.text :top="14">Nela estão os pontos conversados e as orientações do consultor, para você consultar sempre que precisar.</x-email.text>
    <x-email.button :url="$url">Acessar ata</x-email.button>
</x-email.layout>
