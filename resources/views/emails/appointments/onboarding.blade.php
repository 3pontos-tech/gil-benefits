@use('App\Support\Mail\MailText')
@php
    $app = config('app.name');
    $img = fn (string $name): string => asset('images/emails/onboarding/' . $name . '.jpg');
@endphp
<x-email.layout :title="__('appointments::mail.onboarding.subject', ['app' => $app])" :preheader="__('emails.appointments.onboarding.preheader')" :closing="__('emails.layout.reply')">
    <x-email.heading :lines="[
        [__('emails.appointments.onboarding.heading_1'), 'bold'],
        [__('emails.appointments.onboarding.heading_2', ['app' => $app]), 'light'],
        [__('emails.appointments.onboarding.heading_3', ['name' => $userName]), 'bold'],
    ]" />

    <x-email.text size="lg">{{ __('emails.appointments.onboarding.intro') }}</x-email.text>
    <x-email.text size="lg" :top="14">{{ __('emails.appointments.onboarding.steps_intro') }}</x-email.text>

    {{-- Etapa 1: o caminho até o login depende de quem criou a senha. --}}
    <x-email.subheading :badge="__('emails.appointments.onboarding.step', ['number' => 1])" :light="__('emails.appointments.onboarding.step_1_light')" :bold="__('emails.appointments.onboarding.step_1_bold')" />
    @if ($hasTemporaryPassword)
        <x-email.bullet>{{ MailText::inline(__('emails.appointments.onboarding.step_1_find_welcome', ['subject' => $welcomeSubject])) }}</x-email.bullet>
        <x-email.bullet>{{ MailText::inline(__('emails.appointments.onboarding.step_1_open_welcome')) }}</x-email.bullet>
        <x-email.bullet>{{ MailText::inline(__('emails.appointments.onboarding.step_1_login_temporary')) }}</x-email.bullet>
    @else
        <x-email.bullet>{{ MailText::inline(__('emails.appointments.onboarding.step_1_open_platform', ['app' => $app])) }}</x-email.bullet>
        <x-email.bullet>{{ MailText::inline(__('emails.appointments.onboarding.step_1_login_own')) }}</x-email.bullet>
    @endif
    <x-email.image :src="$img('login')" :alt="__('emails.appointments.onboarding.step_1_image', ['app' => $app])" :width="400" />

    <x-email.subheading :badge="__('emails.appointments.onboarding.step', ['number' => 2])" :light="__('emails.appointments.onboarding.step_2_light')" :bold="__('emails.appointments.onboarding.step_2_bold')" />
    <x-email.bullet>{{ __('emails.appointments.onboarding.step_2_anamnese') }}</x-email.bullet>
    <x-email.image :src="$img('anamnese')" :alt="__('emails.appointments.onboarding.step_2_image')" :width="440" />

    <x-email.subheading :badge="__('emails.appointments.onboarding.step', ['number' => 3])" :light="__('emails.appointments.onboarding.step_3_light')" :bold="__('emails.appointments.onboarding.step_3_bold')" />
    <x-email.bullet>{{ MailText::inline(__('emails.appointments.onboarding.step_3_dashboard')) }}</x-email.bullet>
    <x-email.image :src="$img('dashboard')" :alt="__('emails.appointments.onboarding.step_3_dashboard_image')" />
    <x-email.bullet>{{ MailText::inline(__('emails.appointments.onboarding.step_3_category')) }}</x-email.bullet>
    <x-email.image :src="$img('category')" :alt="__('emails.appointments.onboarding.step_3_category_image')" />
    <x-email.bullet>{{ MailText::inline(__('emails.appointments.onboarding.step_3_date', ['days' => $bookingLeadDays])) }}</x-email.bullet>
    <x-email.image :src="$img('date-time')" :alt="__('emails.appointments.onboarding.step_3_date_image')" />
    <x-email.bullet>{{ MailText::inline(__('emails.appointments.onboarding.step_3_confirm')) }}</x-email.bullet>
    <x-email.image :src="$img('summary')" :alt="__('emails.appointments.onboarding.step_3_confirm_image')" />

    <x-email.infobox :bold="__('emails.appointments.onboarding.good_to_know_bold')" :light="__('emails.appointments.onboarding.good_to_know_light')" :top="40">
        <x-email.note :label="__('emails.appointments.onboarding.cancellation_label')">{{ __('emails.appointments.onboarding.cancellation', ['hours' => $cancellationHours]) }}</x-email.note>
        <x-email.note :label="__('emails.appointments.onboarding.reschedule_label')">{{ __('emails.appointments.onboarding.reschedule', ['hours' => $rescheduleHours]) }}</x-email.note>    </x-email.infobox>

    <x-email.cta :bold="__('emails.appointments.onboarding.cta_bold')" :light="__('emails.appointments.onboarding.cta_light')" :url="$loginUrl" :label="__('emails.appointments.onboarding.button', ['app' => $app])" />
</x-email.layout>
