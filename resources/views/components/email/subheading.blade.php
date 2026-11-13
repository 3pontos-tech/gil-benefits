{{-- Título de seção: parte fina, parte forte e, opcionalmente, a etiqueta de etapa acima. --}}
@props(['light' => null, 'bold' => null, 'badge' => null, 'top' => 36])
<tr><td class="px" style="padding:{{ $top }}px 40px 0 40px;">
  @if (filled($badge))
  <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
    <td style="background-color:#FF2A3F; background-image:linear-gradient(90deg,#FF0042 0%,#FF7C27 100%); padding:8px 14px; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:13px; font-weight:700; color:#FFFFFF; letter-spacing:1px; text-transform:uppercase;">{{ $badge }}</td>
  </tr></table>
  @endif
  <p style="margin:{{ filled($badge) ? 14 : 0 }}px 0 0 0; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:24px; line-height:28px; color:#FF0042; text-transform:uppercase;">@if (filled($light))<span style="font-weight:300;">{{ $light }}</span>@endif @if (filled($bold))<span style="font-weight:700;">{{ $bold }}</span>@endif</p>
</td></tr>
