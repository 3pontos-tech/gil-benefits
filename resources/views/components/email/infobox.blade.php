{{--
    Bloco escuro de avisos (x-email.note), com título forte e fino e, opcionalmente, um botão
    vazado no fim.
--}}
@props(['bold' => null, 'light' => null, 'buttonUrl' => null, 'buttonLabel' => null, 'top' => 32])
<tr><td class="px" style="padding:{{ $top }}px 40px 0 40px;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#2B2B2B;">
    <tr><td style="padding:24px 26px; font-family:'Space Grotesk', Arial, Helvetica, sans-serif;">
      @if (filled($bold) || filled($light))
      <p style="margin:0 0 12px 0; font-size:18px; line-height:22px; color:#FFFFFF; text-transform:uppercase;">@if (filled($bold))<span style="font-weight:700;">{{ $bold }}</span>@endif @if (filled($light))<span style="font-weight:300;">{{ $light }}</span>@endif</p>
      @endif
      {{ $slot }}
      @if (filled($buttonUrl))
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:18px;"><tr><td style="border:1px solid #FFFFFF;">
        <a href="{{ $buttonUrl }}" style="display:inline-block; padding:12px 22px; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:14px; font-weight:700; color:#FFFFFF; text-decoration:none; text-transform:uppercase;">{{ $buttonLabel }}</a>
      </td></tr></table>
      @endif
    </td></tr>
  </table>
</td></tr>
