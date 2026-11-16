@use('App\Support\Mail\MailText')
@php($date = $appointmentAt->format('d/m/Y \à\s H:i'))
<x-email.layout :title="__('appointments::mail.scheduled.subject')" :preheader="__('emails.appointments.scheduled.preheader', ['name' => $userName, 'date' => $date])">
    <x-email.heading :lines="[
        [__('emails.appointments.scheduled.heading_1'), 'bold'],
        [__('emails.appointments.scheduled.heading_2'), 'light'],
    ]" />

    <x-email.text size="lg">{{ MailText::inline(__('emails.appointments.scheduled.greeting', ['name' => $consultantName])) }}</x-email.text>
    <x-email.text size="lg" :top="14">{{ MailText::inline(__('emails.appointments.scheduled.body', ['name' => $userName])) }}</x-email.text>

    <x-email.details>
        <x-email.detail :label="__('emails.appointments.scheduled.date_time')">{{ $date }} {{ __('emails.layout.timezone') }}</x-email.detail>
        @if ($notes)
            <x-email.detail :label="__('emails.appointments.scheduled.employee_notes')">{{ MailText::inline($notes, MailText::ACCENT) }}</x-email.detail>
        @endif
        @if ($meetingUrl)
            <x-email.detail :label="__('emails.appointments.scheduled.meeting_link')" :href="$meetingUrl" highlight>{{ $meetingUrl }}</x-email.detail>
        @endif
    </x-email.details>

    <x-email.text :top="12">{{ __('emails.appointments.scheduled.panel_description') }}</x-email.text>
    <x-email.button :url="$panelUrl">{{ __('emails.appointments.scheduled.button') }}</x-email.button>
</x-email.layout>
