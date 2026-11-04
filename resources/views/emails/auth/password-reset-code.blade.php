<x-mail::message>
# {{ __('api::mail.password_reset_code.title') }}

{{ __('api::mail.password_reset_code.intro', ['app' => config('app.name'), 'minutes' => $minutes]) }}

<x-mail::panel>
<span style="font-size: 28px; font-weight: 700; letter-spacing: 6px;">{{ $code }}</span>
</x-mail::panel>

{{ __('api::mail.password_reset_code.ignore') }}

{{ config('app.name') }}
</x-mail::message>
