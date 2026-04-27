<x-mail::message>
# {{ __('emails.appointments.cancelled_late.title') }}

{{ __('emails.appointments.cancelled_late.greeting', ['name' => $userName]) }}

{{ __('emails.appointments.cancelled_late.body', ['consultant' => $consultantName, 'date' => $appointmentAt->format('d/m/Y \à\s H:i')]) }}

{{ __('emails.appointments.cancelled_late.credit_notice') }}

{{ __('emails.appointments.cancelled_late.reschedule') }}

<x-mail::button :url="$panelUrl" color="error">
{{ __('emails.appointments.cancelled_late.button') }}
</x-mail::button>

{{ __('emails.appointments.cancelled_late.support') }}

{{ __('emails.appointments.cancelled_late.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
