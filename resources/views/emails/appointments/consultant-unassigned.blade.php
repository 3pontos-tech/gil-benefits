@use('App\Support\Mail\MailText')
@php($date = $appointmentAt->format('d/m/Y \à\s H:i'))
<x-email.layout :title="__('appointments::mail.consultant_unassigned.subject')" :preheader="__('emails.appointments.consultant_unassigned.preheader', ['name' => $userName])">
    <x-email.heading :lines="[
        [__('emails.appointments.consultant_unassigned.heading_1'), 'bold'],
        [__('emails.appointments.consultant_unassigned.heading_2'), 'light'],
    ]" />

    <x-email.text size="lg">{{ MailText::inline(__('emails.appointments.consultant_unassigned.greeting', ['name' => $consultantName])) }}</x-email.text>
    <x-email.text size="lg" :top="14">{{ MailText::inline(__('emails.appointments.consultant_unassigned.body', ['name' => $userName])) }}</x-email.text>

    <x-email.details>
        <x-email.detail :label="__('emails.appointments.consultant_unassigned.date_time')">{{ $date }} {{ __('emails.layout.timezone') }}</x-email.detail>
    </x-email.details>

    <x-email.text :top="12">{{ __('emails.appointments.consultant_unassigned.panel_description') }}</x-email.text>
    <x-email.button :url="$panelUrl">{{ __('emails.appointments.consultant_unassigned.button') }}</x-email.button>
</x-email.layout>
