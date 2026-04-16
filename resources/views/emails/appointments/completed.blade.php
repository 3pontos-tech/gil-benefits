<x-mail::message>
# {{ __('emails.appointments.completed.title') }}

{{ __('emails.appointments.completed.greeting', ['name' => $userName]) }}

{{ __('emails.appointments.completed.body', ['consultant' => $consultantName, 'date' => $appointmentAt->format('d/m/Y \à\s H:i')]) }}

{{ __('emails.appointments.completed.feedback') }}

<x-mail::button :url="$panelUrl">
{{ __('emails.appointments.completed.button') }}
</x-mail::button>

{{ __('emails.appointments.completed.help') }}

{{ __('emails.appointments.completed.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
