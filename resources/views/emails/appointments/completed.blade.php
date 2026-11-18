@use('App\Support\Mail\MailText')
@php($date = $appointmentAt->format('d/m/Y \à\s H:i'))
<x-email.layout :title="__('appointments::mail.completed.subject')" :preheader="__('emails.appointments.completed.preheader', ['consultant' => $consultantName])" :closing="__('emails.appointments.completed.help')">
    <x-email.heading :lines="[
        [__('emails.appointments.completed.heading_1'), 'bold'],
        [__('emails.appointments.completed.heading_2'), 'light'],
        [__('emails.appointments.completed.heading_3', ['name' => $userName]), 'bold'],
    ]" />

    <x-email.text size="lg">{{ MailText::inline(__('emails.appointments.completed.body', ['consultant' => $consultantName, 'date' => $date])) }}</x-email.text>
    <x-email.text size="lg" :top="14">{{ __('emails.appointments.completed.feedback') }}</x-email.text>
    <x-email.button :url="$panelUrl">{{ __('emails.appointments.completed.button') }}</x-email.button>
</x-email.layout>
