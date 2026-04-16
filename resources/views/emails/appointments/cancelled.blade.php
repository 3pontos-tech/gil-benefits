<x-mail::message>
# Consulta Cancelada

Olá, **{{ $userName }}**!

Informamos que a sua consulta com **{{ $consultantName }}**, que estava agendada para **{{ $appointmentAt->format('d/m/Y \à\s H:i') }}**, foi cancelada.

Se o cancelamento foi feito por engano ou se desejar reagendar, acesse o painel e crie uma nova solicitação.

<x-mail::button :url="$panelUrl" color="error">
Acessar painel
</x-mail::button>

Se tiver dúvidas sobre o cancelamento, entre em contato com o suporte.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
