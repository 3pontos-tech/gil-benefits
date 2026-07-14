<x-mail::message>
# {{ __('emails.appointments.consultant_unassigned.title') }}

{{ __('emails.appointments.consultant_unassigned.greeting', ['name' => $consultantName]) }}

{{ __('emails.appointments.consultant_unassigned.body', ['name' => $userName]) }}

<x-mail::panel>
**{{ __('emails.appointments.consultant_unassigned.date_time') }}** {{ $appointmentAt->format('d/m/Y \à\s H:i') }}
</x-mail::panel>

{{ __('emails.appointments.consultant_unassigned.panel_description') }}

<x-mail::button :url="$panelUrl">
{{ __('emails.appointments.consultant_unassigned.button') }}
</x-mail::button>

{{ __('emails.appointments.consultant_unassigned.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
