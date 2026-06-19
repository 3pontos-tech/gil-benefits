<x-mail::message>
# Novo chamado: {{ $ticket->subject }}

**Protocolo:** {{ $ticket->protocol }}
**Categoria:** {{ $ticket->category->getLabel() }}
**Área:** {{ $channelName }}

---

**Solicitante:** {{ $ticket->getRequesterName() ?? '—' }}
**E-mail:** {{ $ticket->getRequesterEmail() ?? '—' }}
@if($ticket->company)
**Empresa:** {{ $ticket->company->name }}
@elseif($ticket->visitor_company_name)
**Empresa:** {{ $ticket->visitor_company_name }}
@endif

---

**Assunto:** {{ $ticket->subject }}

{{ $ticket->description }}

---

@if($ticket->url)
**URL:** {{ $ticket->url }}
@endif
@if($ticket->browser)
**Navegador:** {{ $ticket->browser }}
@endif
@if($ticket->device)
**Dispositivo:** {{ $ticket->device }}
@endif
@if($ticket->environment)
**Ambiente:** {{ $ticket->environment }}
@endif

*Aberto em {{ $ticket->created_at->format('d/m/Y H:i') }}*
</x-mail::message>
