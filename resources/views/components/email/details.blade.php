{{-- Bloco de linhas rótulo e valor (x-email.detail). --}}
@props(['top' => 22])
<tr><td class="px" style="padding:{{ $top }}px 40px 0 40px;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    {{ $slot }}
  </table>
</td></tr>
