{{-- Fecho em gradiente com chamada e botão branco. --}}
@props(['bold' => null, 'light' => null, 'url', 'label', 'top' => 20])
<tr><td class="px" style="padding:{{ $top }}px 40px 0 40px;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FF2A3F; background-image:linear-gradient(90deg,#FF0042 0%,#FF7C27 100%);">
    <tr><td style="padding:26px 26px;" align="left">
      @if (filled($bold) || filled($light))
      <p style="margin:0 0 16px 0; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:22px; line-height:26px; color:#FFFFFF; text-transform:uppercase;">@if (filled($bold))<span style="font-weight:700;">{{ $bold }}</span>@endif @if (filled($light))<span style="font-weight:300;">{{ $light }}</span>@endif</p>
      @endif
      <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#FFFFFF;">
        <a href="{{ $url }}" style="display:inline-block; padding:14px 26px; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:15px; font-weight:700; color:#FF0042; text-decoration:none; text-transform:uppercase;">{{ $label }}</a>
      </td></tr></table>
    </td></tr>
  </table>
</td></tr>
