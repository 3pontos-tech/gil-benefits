<x-mail::message>
# {{ __('user::mail.password_changed.title') }}

{{ __('user::mail.password_changed.greeting', ['name' => $userName]) }}

{{ __('user::mail.password_changed.body', ['date' => $date, 'time' => $time]) }}

{{ __('user::mail.password_changed.signed_out') }}

{{ __('user::mail.password_changed.not_you') }}

<x-mail::button :url="$resetUrl">
{{ __('user::mail.password_changed.button') }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
