@use('App\Support\Mail\MailText')
@php($date = $appointmentAt->format('d/m/Y \à\s H:i'))
<x-email.layout :title="__('appointments::mail.cancelled.subject')" :preheader="__('emails.appointments.cancelled.preheader', ['consultant' => $consultantName, 'date' => $date])" :closing="__('emails.appointments.cancelled.support')">
    <x-email.heading :lines="[
        [__('emails.appointments.cancelled.heading_1', ['name' => $userName]), 'light'],
        [__('emails.appointments.cancelled.heading_2'), 'bold'],
        [__('emails.appointments.cancelled.heading_3'), 'bold'],
    ]" />

    <x-email.text size="lg">{{ MailText::inline(__('emails.appointments.cancelled.body', ['consultant' => $consultantName, 'date' => $date])) }}</x-email.text>

    <x-email.infobox :bold="__('emails.appointments.cancelled.reschedule_bold')" :light="__('emails.appointments.cancelled.reschedule_light')" :button-url="$panelUrl" :button-label="__('emails.appointments.cancelled.button')">
        <x-email.note>{{ __('emails.appointments.cancelled.reschedule') }}</x-email.note>
    </x-email.infobox>
</x-email.layout>
