{{-- Botão principal no meio do texto: gradiente com texto branco. --}}
@props(['url', 'top' => 24])
<tr><td class="px" style="padding:{{ $top }}px 40px 0 40px;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#FF2A3F; background-image:linear-gradient(90deg,#FF0042 0%,#FF7C27 100%);">
    <a href="{{ $url }}" style="display:inline-block; padding:14px 26px; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:15px; font-weight:700; color:#FFFFFF; text-decoration:none; text-transform:uppercase;">{{ $slot }}</a>
  </td></tr></table>
</td></tr>
