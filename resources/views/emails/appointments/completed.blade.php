<x-mail::message>
# Consulta Concluída

Olá, **{{ $userName }}**!

Sua consulta com **{{ $consultantName }}**, realizada em **{{ $appointmentAt->format('d/m/Y \à\s H:i') }}**, foi concluída com sucesso.

Esperamos que a sessão tenha sido útil para você. Caso queira deixar um feedback sobre o atendimento, acesse o painel abaixo.

<x-mail::button :url="$panelUrl">
Avaliar consulta
</x-mail::button>

Caso tenha alguma dúvida ou precise agendar uma nova consulta, estamos à disposição.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
