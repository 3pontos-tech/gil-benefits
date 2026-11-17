@use('App\Support\Mail\MailText')
<x-email.layout :title="__('support::mail.reopened.subject', ['protocol' => $ticket->protocol, 'subject' => $ticket->subject])" :preheader="__('support::mail.reopened.preheader', ['protocol' => $ticket->protocol])">
    <x-email.heading :lines="[
        [__('support::mail.reopened.heading'), 'bold'],
        [$ticket->protocol, 'light'],
    ]" />

    <x-email.details>
        <x-email.detail :label="__('support::mail.reopened.labels.category')">{{ $ticket->category->getLabel() }}</x-email.detail>
        <x-email.detail :label="__('support::mail.reopened.labels.requester')">{{ $ticket->getRequesterName() ?? '—' }}</x-email.detail>
        <x-email.detail :label="__('support::mail.reopened.labels.reopened_by')">{{ $reopenedBy ?? '—' }}</x-email.detail>
    </x-email.details>

    <x-email.subheading :bold="__('support::mail.reopened.labels.reason')" :top="28" />
    <x-email.text :top="12">{{ filled($reason) ? MailText::inline($reason) : '—' }}</x-email.text>

    <x-email.subheading :light="__('support::mail.reopened.labels.subject')" :bold="$ticket->subject" :top="28" />
    <x-email.text :top="12">{{ MailText::inline($ticket->description) }}</x-email.text>

    <x-email.text size="sm" :top="16">{{ __('support::mail.reopened.opened_at', ['date' => $ticket->created_at->format('d/m/Y H:i')]) }}</x-email.text>
</x-email.layout>
