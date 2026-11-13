{{-- Item de lista com o marcador quadrado do Flamma. --}}
@props(['top' => 16])
<tr><td class="px" style="padding:{{ $top }}px 40px 0 40px;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
    <td width="18" valign="top" style="padding-top:8px;"><div style="width:8px; height:8px; background-color:#FF0042;"></div></td>
    <td style="font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:15px; line-height:23px; color:#4A4A4A;">{{ $slot }}</td>
  </tr></table>
</td></tr>
