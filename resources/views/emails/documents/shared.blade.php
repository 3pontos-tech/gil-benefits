<x-mail::message>
# Novo Documento Compartilhado

Olá, **{{ $employeeName }}**!

@if($consultantName)
O(a) consultor(a) **{{ $consultantName }}** compartilhou um documento com você.
@else
Um novo documento foi compartilhado com você.
@endif

<x-mail::panel>
**Documento:** {{ $documentTitle }}
</x-mail::panel>

Para visualizar o documento, acesse a plataforma.

<x-mail::button :url="$panelUrl">
Ver documento
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
