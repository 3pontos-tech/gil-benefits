{{-- Captura de tela de um passo, com a borda clara dos templates. --}}
@props(['src', 'alt', 'width' => 520])
<tr><td class="px" style="padding:14px 40px 0 40px;">
  <img src="{{ $src }}" alt="{{ $alt }}" width="{{ $width }}" style="display:block; width:100%; max-width:{{ $width }}px; height:auto; border:1px solid #DDDDDD;">
</td></tr>
