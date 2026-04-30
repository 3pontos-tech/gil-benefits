<x-mail::message>
# {{ __('emails.appointments.scheduled.title') }}

{{ __('emails.appointments.scheduled.greeting', ['name' => $consultantName]) }}

{{ __('emails.appointments.scheduled.body', ['name' => $userName]) }}

<x-mail::panel>
**{{ __('emails.appointments.scheduled.date_time') }}** {{ $appointmentAt->format('d/m/Y \à\s H:i') }}

@if($meetingUrl)
**{{ __('emails.appointments.scheduled.meeting_link') }}** [{{ __('emails.appointments.scheduled.meeting_link_label') }}]({{ $meetingUrl }})
@endif

@if($notes)
**{{ __('emails.appointments.scheduled.employee_notes') }}**
{{ $notes }}
@endif
</x-mail::panel>

{{ __('emails.appointments.scheduled.panel_description') }}

<x-mail::button :url="$panelUrl">
{{ __('emails.appointments.scheduled.button') }}
</x-mail::button>

{{ __('emails.appointments.scheduled.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
