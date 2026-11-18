{{--
    Moldura dos e-mails do Flamma: logo, conteúdo, despedida e rodapé. Tudo em tabelas com estilo
    inline, porque Gmail e Outlook descartam o <style> e o CSS moderno. A fonte Space Grotesk só
    carrega nos clientes que aceitam web font; nos outros cai para Arial.
--}}
@use('App\Support\Mail\MailText')
@props([
    'preheader' => null,
    'title' => config('app.name'),
    'closing' => null,
    'signature' => __('emails.layout.signature', ['app' => config('app.name')]),
])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light only">
<title>{{ $title }}</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;700&display=swap" rel="stylesheet">
<style>
  @media only screen and (max-width: 620px) {
    .container { width: 100% !important; }
    .px { padding-left: 22px !important; padding-right: 22px !important; }
    .h1 { font-size: 32px !important; line-height: 36px !important; }
    .lbl { width: 92px !important; font-size: 12px !important; padding: 14px 10px !important; }
  }
</style>
</head>
<body style="margin:0; padding:0; background-color:#EEEEEE;">
@if (filled($preheader))
<div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; color:#EEEEEE; line-height:1px;">{{ $preheader }}</div>
@endif
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#EEEEEE;">
<tr><td align="center" style="padding:32px 12px;">
  <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#FFFFFF;">

    <tr><td class="px" style="padding:32px 40px 0 40px;">
      <img src="{{ asset('images/emails/flamma-logo.png') }}" alt="{{ config('app.name') }}" width="140" style="display:block; width:140px; height:auto; border:0;">
    </td></tr>

    {{ $slot }}

    <tr><td class="px" style="padding:28px 40px 32px 40px; font-family:'Space Grotesk', Arial, Helvetica, sans-serif;">
      @if (filled($closing))
      <p style="margin:0 0 12px 0; font-size:15px; line-height:22px; color:#4A4A4A;">{{ MailText::inline($closing) }}</p>
      @endif
      <p style="margin:0; font-size:15px; line-height:22px; color:#4A4A4A;">{{ __('emails.layout.regards') }}<br><strong style="color:#FF0042;">{{ $signature }}</strong></p>
    </td></tr>

    <tr><td class="px" style="background-color:#2B2B2B; padding:22px 40px; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:12px; line-height:18px; color:#BDBDBD; text-align:center;">
      <strong style="color:#FFFFFF;">{{ config('app.name') }}</strong> · {{ __('emails.layout.tagline') }}
    </td></tr>

  </table>
</td></tr>
</table>
</body>
</html>
