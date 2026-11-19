@use('App\Support\Mail\MailText')
@php($app = config('app.name'))
<x-email.layout :title="__('user::mail.welcome.subject', ['app_name' => $app])" :preheader="__('emails.users.welcome.preheader', ['app' => $app])" :closing="__('emails.layout.reply')">
    <x-email.heading :lines="[
        [__('emails.users.welcome.heading_1'), 'bold'],
        [__('emails.users.welcome.heading_2', ['app' => $app]), 'light'],
        [__('emails.users.welcome.heading_3', ['name' => $userName]), 'bold'],
    ]" />

    @if (filled($setPasswordUrl))
        <x-email.text size="lg">{{ __('emails.users.welcome.set_password') }}</x-email.text>
        <x-email.button :url="$setPasswordUrl">{{ __('emails.users.welcome.set_password_button') }}</x-email.button>
        <x-email.text size="sm" :top="14">{{ __('emails.users.welcome.set_password_expires', ['minutes' => $setPasswordMinutes]) }}</x-email.text>
    @else
        <x-email.text size="lg">{{ __('emails.users.welcome.intro') }}</x-email.text>

        <x-email.subheading :light="__('emails.users.welcome.features_light')" :bold="__('emails.users.welcome.features_bold')" />
        <x-email.bullet>{{ MailText::inline(__('emails.users.welcome.feature_appointments')) }}</x-email.bullet>
        <x-email.bullet :top="10">{{ MailText::inline(__('emails.users.welcome.feature_documents')) }}</x-email.bullet>
        <x-email.bullet :top="10">{{ MailText::inline(__('emails.users.welcome.feature_history')) }}</x-email.bullet>

        @if (filled($password))
            <x-email.subheading :light="__('emails.users.welcome.credentials_light')" :bold="__('emails.users.welcome.credentials_bold')" />
            <x-email.text :top="16">{{ __('emails.users.welcome.admin_created') }}</x-email.text>
            <x-email.details :top="16">
                <x-email.detail :label="__('emails.users.welcome.email_label')">{{ $userEmail }}</x-email.detail>
                <x-email.detail :label="__('emails.users.welcome.temp_password_label')" highlight>{{ $password }}</x-email.detail>
            </x-email.details>
            <x-email.text size="sm" :top="6">{{ __('emails.users.welcome.security_note') }}</x-email.text>
        @else
            <x-email.text :top="24">{{ __('emails.users.welcome.login_prompt') }}</x-email.text>
        @endif

        <x-email.cta :top="32" :bold="__('emails.users.welcome.cta_bold')" :light="__('emails.users.welcome.cta_light')" :url="$panelUrl" :label="__('emails.users.welcome.button')" />
    @endif
</x-email.layout>
