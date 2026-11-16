{{-- Código para digitar no app, grande e espaçado no bloco escuro. --}}
@props(['top' => 22])
<tr><td class="px" style="padding:{{ $top }}px 40px 0 40px;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#2B2B2B;">
    <tr><td align="center" style="padding:26px 16px; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:34px; line-height:40px; font-weight:700; letter-spacing:8px; color:#FFFFFF;">{{ $slot }}</td></tr>
  </table>
</td></tr>
