<x-mail::message>
# {{ __('emails.appointments.requested_admin.title') }}

{{ __('emails.appointments.requested_admin.greeting') }}

{{ __('emails.appointments.requested_admin.body') }}

<x-mail::panel>
**{{ __('emails.appointments.requested_admin.employee') }}** {{ $userName }}

**{{ __('emails.appointments.requested_admin.category') }}** {{ $categoryLabel }}

**{{ __('emails.appointments.requested_admin.date_time') }}** {{ $appointmentAt->format('d/m/Y \à\s H:i') }}

@if($notes)
**{{ __('emails.appointments.requested_admin.employee_notes') }}**
{{ $notes }}
@endif
</x-mail::panel>

{{ __('emails.appointments.requested_admin.panel_description') }}

<x-mail::button :url="$panelUrl">
{{ __('emails.appointments.requested_admin.button') }}
</x-mail::button>

{{ __('emails.appointments.requested_admin.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
