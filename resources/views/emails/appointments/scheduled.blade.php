<x-mail::message>
# Nova Consulta Agendada

Olá, **{{ $consultantName }}**!

Você tem uma nova consulta confirmada com **{{ $userName }}**.

<x-mail::panel>
**Data e hora:** {{ $appointmentAt->format('d/m/Y \à\s H:i') }}

@if($meetingUrl)
**Link da reunião:** [Acessar reunião]({{ $meetingUrl }})
@endif

@if($notes)
**Observações do funcionário:**
{{ $notes }}
@endif
</x-mail::panel>

Acesse o painel para visualizar todos os detalhes da consulta.

<x-mail::button :url="$panelUrl">
Acessar painel
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
