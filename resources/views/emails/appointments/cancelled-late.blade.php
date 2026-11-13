@use('App\Support\Mail\MailText')
@php($date = $appointmentAt->format('d/m/Y \à\s H:i'))
<x-email.layout :title="__('appointments::mail.user_cancelled_late.subject')" :preheader="__('emails.appointments.cancelled_late.preheader', ['date' => $date])" :closing="__('emails.appointments.cancelled_late.support')">
    <x-email.heading :lines="[
        [__('emails.appointments.cancelled_late.heading_1', ['name' => $userName]), 'light'],
        [__('emails.appointments.cancelled_late.heading_2'), 'bold'],
        [__('emails.appointments.cancelled_late.heading_3'), 'bold'],
    ]" />

    <x-email.text size="lg">{{ MailText::inline(__('emails.appointments.cancelled_late.body', ['consultant' => $consultantName, 'date' => $date, 'hours' => $freeWindowHours])) }}</x-email.text>

    <x-email.infobox :bold="__('emails.appointments.cancelled_late.notes_bold')" :light="__('emails.appointments.cancelled_late.notes_light')" :button-url="$panelUrl" :button-label="__('emails.appointments.cancelled_late.button')">
        <x-email.note :label="__('emails.appointments.cancelled_late.credit_label')">{{ __('emails.appointments.cancelled_late.credit_notice') }}</x-email.note>
        <x-email.note :label="__('emails.appointments.cancelled_late.reschedule_label')">{{ __('emails.appointments.cancelled_late.reschedule') }}</x-email.note>
    </x-email.infobox>
</x-email.layout>
