@use('App\Support\Mail\MailText')
@php($date = $appointmentAt->format('d/m/Y \à\s H:i'))
<x-email.layout :title="__('appointments::mail.requested_admin.subject')" :preheader="__('emails.appointments.requested_admin.preheader', ['name' => $userName, 'date' => $date])">
    <x-email.heading :lines="[
        [__('emails.appointments.requested_admin.heading_1'), 'bold'],
        [__('emails.appointments.requested_admin.heading_2'), 'light'],
    ]" />

    <x-email.text size="lg">{{ __('emails.appointments.requested_admin.greeting') }}</x-email.text>
    <x-email.text size="lg" :top="14">{{ __('emails.appointments.requested_admin.body') }}</x-email.text>

    <x-email.details>
        <x-email.detail :label="__('emails.appointments.requested_admin.employee')">{{ $userName }}</x-email.detail>
        <x-email.detail :label="__('emails.appointments.requested_admin.category')">{{ $categoryLabel }}</x-email.detail>
        <x-email.detail :label="__('emails.appointments.requested_admin.date_time')">{{ $date }} {{ __('emails.layout.timezone') }}</x-email.detail>
        @if ($notes)
            <x-email.detail :label="__('emails.appointments.requested_admin.employee_notes')">{{ MailText::inline($notes, MailText::ACCENT) }}</x-email.detail>
        @endif
    </x-email.details>

    <x-email.text :top="12">{{ __('emails.appointments.requested_admin.panel_description') }}</x-email.text>
    <x-email.button :url="$panelUrl">{{ __('emails.appointments.requested_admin.button') }}</x-email.button>
</x-email.layout>
