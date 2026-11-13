{{--
    Parágrafo. `lg` é a abertura do e-mail, `md` o corpo e `sm` as notas em cinza. O conteúdo vem
    pelo slot, normalmente MailText::inline(__('...')).
--}}
@props(['size' => 'md', 'top' => 20])
@php
    [$fontSize, $lineHeight, $color] = match ($size) {
        'lg' => [16, 25, '#2B2B2B'],
        'sm' => [14, 21, '#7A7A7A'],
        default => [15, 23, '#4A4A4A'],
    };
@endphp
<tr><td class="px" style="padding:{{ $top }}px 40px 0 40px;">
  <p style="margin:0; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:{{ $fontSize }}px; line-height:{{ $lineHeight }}px; color:{{ $color }};">{{ $slot }}</p>
</td></tr>
