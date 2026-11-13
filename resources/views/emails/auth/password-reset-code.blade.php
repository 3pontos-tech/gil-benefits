<x-email.layout :title="__('api::mail.password_reset_code.title')" :preheader="__('api::mail.password_reset_code.intro', ['app' => config('app.name'), 'minutes' => $minutes])">
    <x-email.heading :lines="[
        [__('api::mail.password_reset_code.heading_1'), 'light'],
        [__('api::mail.password_reset_code.heading_2'), 'bold'],
    ]" />

    <x-email.text size="lg">{{ __('api::mail.password_reset_code.intro', ['app' => config('app.name'), 'minutes' => $minutes]) }}</x-email.text>
    <x-email.code>{{ $code }}</x-email.code>
    <x-email.text size="sm" :top="14">{{ __('api::mail.password_reset_code.ignore') }}</x-email.text>
</x-email.layout>
