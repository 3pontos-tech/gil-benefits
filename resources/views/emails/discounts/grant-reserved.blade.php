@use('App\Support\Mail\MailText')
@php($app = config('app.name'))
<x-email.layout :title="__('emails.discounts.grant_reserved.subject', ['percent' => $percent])" :preheader="__('emails.discounts.grant_reserved.preheader', ['percent' => $percent])" :closing="__('emails.layout.reply')">
    <x-email.heading :lines="[
        [__('emails.discounts.grant_reserved.heading_1'), 'bold'],
        [__('emails.discounts.grant_reserved.heading_2'), 'light'],
    ]" />

    <x-email.text size="lg">{{ $greeting }}</x-email.text>
    <x-email.text :top="14">{{ MailText::inline(__('emails.discounts.grant_reserved.intro', ['percent' => $percent])) }}</x-email.text>
    <x-email.text :top="14">{{ MailText::inline(__('emails.discounts.grant_reserved.steps', ['expires' => $expiresAt])) }}</x-email.text>

    <x-email.button :url="$activationUrl">{{ __('emails.discounts.grant_reserved.button') }}</x-email.button>

    <x-email.text size="sm" :top="24">{{ __('emails.discounts.grant_reserved.ignore') }}</x-email.text>
</x-email.layout>
