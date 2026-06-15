<x-mail::message>
# {{ $ticket->protocol }}

Olá, {{ $requesterName ?? 'usuário' }}!

{{ $isResolved ? __('support::mail.status_updated.resolved_intro') : __('support::mail.status_updated.closed_intro') }}

<x-mail::panel>
**Protocolo:** {{ $ticket->protocol }}
**Assunto:** {{ $ticket->subject }}
**Status:** {{ $ticket->status->getLabel() }}
</x-mail::panel>

Caso precise de mais informações, responda a este e-mail informando o número do protocolo.

Atenciosamente,
**Time Flamma**
</x-mail::message>
