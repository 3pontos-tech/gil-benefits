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

@if(filled($setPasswordUrl))
{{ __('emails.users.welcome.set_password') }}

<x-mail::button :url="$setPasswordUrl">
{{ __('emails.users.welcome.set_password_button') }}
</x-mail::button>

{{ __('emails.users.welcome.set_password_expires', ['minutes' => config('auth.passwords.users.expire')]) }}

@elseif(filled($password))
{{ __('emails.users.welcome.admin_created') }}

{{ __('emails.users.welcome.email_label', ['email' => $userEmail]) }}

{{ __('emails.users.welcome.temp_password', ['password' => $password]) }}

{{ __('emails.users.welcome.security_note') }}

@else
{{ __('emails.users.welcome.login_prompt') }}

@endif

@if(blank($setPasswordUrl))
<x-mail::button :url="$panelUrl">
{{ __('emails.users.welcome.button') }}
</x-mail::button>
@endif

{{ __('emails.users.welcome.help') }}

{{ __('emails.users.welcome.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
