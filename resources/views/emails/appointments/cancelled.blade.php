<x-mail::message>
# {{ __('emails.appointments.cancelled.title') }}

{{ __('emails.appointments.cancelled.greeting', ['name' => $userName]) }}

{{ __('emails.appointments.cancelled.body', ['consultant' => $consultantName, 'date' => $appointmentAt->format('d/m/Y \à\s H:i')]) }}

{{ __('emails.appointments.cancelled.reschedule') }}

<x-mail::button :url="$panelUrl" color="error">
{{ __('emails.appointments.cancelled.button') }}
</x-mail::button>

{{ __('emails.appointments.cancelled.support') }}

{{ __('emails.appointments.cancelled.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
