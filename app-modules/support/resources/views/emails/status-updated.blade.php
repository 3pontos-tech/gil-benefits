<x-mail::message>
# {{ $ticket->protocol }}

Olá, {{ $requesterName ?? 'usuário' }}!

{{ __('support::mail.status_updated.intros.' . $ticket->status->value) }}

<x-mail::panel>
**Protocolo:** {{ $ticket->protocol }}
**Assunto:** {{ $ticket->subject }}
**Status:** {{ $ticket->status->getLabel() }}
</x-mail::panel>

Caso precise de mais informações, responda a este e-mail informando o número do protocolo.

Atenciosamente,
**Time Flamma**
</x-mail::message>
