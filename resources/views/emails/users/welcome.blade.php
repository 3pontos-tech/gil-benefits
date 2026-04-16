<x-mail::message>
# Bem-vindo ao {{ config('app.name') }}!

Olá, **{{ $userName }}**!

Estamos felizes em tê-lo(a) conosco. Sua conta foi criada com sucesso e você já pode acessar a plataforma de benefícios da sua empresa.

Por aqui você pode:

<x-mail::table>
| O que você pode fazer |
| --------------------- |
| Agendar consultas com consultores especializados |
| Visualizar documentos compartilhados por sua equipe |
| Acompanhar o histórico de seus atendimentos |
</x-mail::table>

@if(filled($password))
Seu acesso foi criado pelo administrador da sua empresa. Use as credenciais abaixo para entrar pela primeira vez:

**E-mail:** {{ $userEmail }}

**Senha temporária:** {{ $password }}

Por segurança, recomendamos que você altere sua senha após o primeiro acesso.

@else
Para começar, acesse a plataforma com suas credenciais de login.

@endif

<x-mail::button :url="$panelUrl">
Acessar plataforma
</x-mail::button>

Se tiver qualquer dúvida, não hesite em entrar em contato.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
