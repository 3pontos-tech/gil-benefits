<x-mail::message>
# Solicitação recebida

Olá, {{ $requesterName ?? 'usuário' }}!

Recebemos sua solicitação e ela foi encaminhada para a área responsável.

<x-mail::panel>
**Protocolo:** {{ $ticket->protocol }}
**Assunto:** {{ $ticket->subject }}
**Encaminhado para:** {{ $channelName }}
</x-mail::panel>

Você pode acompanhar o andamento acessando **Meus Chamados** na plataforma Flamma.

Caso precise de mais informações, responda a este e-mail informando o número do protocolo.

Atenciosamente,
**Time Flamma**
</x-mail::message>
