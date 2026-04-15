@component('mail::message')
Olá, {{ $record->appointment->user->name }}.

Sua ata do atendimento de {{ $record->appointment->appointment_at->format('d/m/Y') }} com **{{ $record->appointment->consultant->name ?? 'seu consultor' }}** já está disponível na plataforma.

@component('mail::button', ['url' => $url])
Acessar ata
@endcomponent

Atenciosamente,
{{ config('app.name') }}
@endcomponent
