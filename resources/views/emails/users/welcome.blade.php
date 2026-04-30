<x-mail::message>
# {{ __('emails.users.welcome.title', ['app' => config('app.name')]) }}

{{ __('emails.users.welcome.greeting', ['name' => $userName]) }}

{{ __('emails.users.welcome.intro') }}

{{ __('emails.users.welcome.features_title') }}:

<x-mail::table>
| {{ __('emails.users.welcome.features_title') }} |
| --------------------- |
| {{ __('emails.users.welcome.feature_appointments') }} |
| {{ __('emails.users.welcome.feature_documents') }} |
| {{ __('emails.users.welcome.feature_history') }} |
</x-mail::table>

@if(filled($password))
{{ __('emails.users.welcome.admin_created') }}

{{ __('emails.users.welcome.email_label', ['email' => $userEmail]) }}

{{ __('emails.users.welcome.temp_password', ['password' => $password]) }}

{{ __('emails.users.welcome.security_note') }}

@else
{{ __('emails.users.welcome.login_prompt') }}

@endif

<x-mail::button :url="$panelUrl">
{{ __('emails.users.welcome.button') }}
</x-mail::button>

{{ __('emails.users.welcome.help') }}

{{ __('emails.users.welcome.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
