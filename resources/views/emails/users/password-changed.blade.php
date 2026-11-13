@use('App\Support\Mail\MailText')
<x-email.layout :title="__('user::mail.password_changed.title')" :preheader="__('user::mail.password_changed.body', ['date' => $date, 'time' => $time])">
    <x-email.heading :lines="[
        [__('user::mail.password_changed.heading_1'), 'light'],
        [__('user::mail.password_changed.heading_2'), 'bold'],
    ]" />

    <x-email.text size="lg">{{ __('user::mail.password_changed.greeting', ['name' => $userName]) }}</x-email.text>
    <x-email.text size="lg" :top="14">{{ __('user::mail.password_changed.body', ['date' => $date, 'time' => $time]) }}</x-email.text>
    <x-email.text :top="14">{{ __('user::mail.password_changed.signed_out') }}</x-email.text>

    <x-email.infobox :bold="__('user::mail.password_changed.not_you_bold')" :light="__('user::mail.password_changed.not_you_light')" :button-url="$resetUrl" :button-label="__('user::mail.password_changed.button')">
        <x-email.note>{{ __('user::mail.password_changed.not_you') }}</x-email.note>
    </x-email.infobox>
</x-email.layout>
