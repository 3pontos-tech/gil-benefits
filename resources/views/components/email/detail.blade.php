{{--
    Uma linha do bloco de detalhes: rótulo em gradiente e valor no fundo escuro. `highlight`
    destaca o valor (o link da sessão); `href` torna o valor clicável.
--}}
@props(['label', 'highlight' => false, 'href' => null])
@php
    $valueStyle = $highlight
        ? 'background-color:#FFF0F3; border:2px solid #FF0042; color:#2B2B2B;'
        : 'background-color:#2B2B2B; color:#FFFFFF;';
@endphp
<tr><td style="padding:0 0 8px 0;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
    <td class="lbl" width="120" valign="top" style="background-color:#FF2A3F; background-image:linear-gradient(90deg,#FF0042 0%,#FF7C27 100%); padding:14px 14px; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:13px; font-weight:700; color:#FFFFFF; letter-spacing:0.5px; text-transform:uppercase;">{{ $label }}</td>
    <td style="{{ $valueStyle }} padding:14px 16px; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:16px; line-height:20px; font-weight:700; word-break:break-word;">@if (filled($href))<a href="{{ $href }}" style="color:{{ $highlight ? '#FF0042' : '#FFFFFF' }}; text-decoration:underline;">{{ $slot }}</a>@else{{ $slot }}@endif</td>
  </tr></table>
</td></tr>
