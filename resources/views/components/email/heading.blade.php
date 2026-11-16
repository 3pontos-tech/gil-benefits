{{-- Título do e-mail, uma linha por item: [texto, 'bold' | 'light']. --}}
@props(['lines' => []])
<tr><td class="px" style="padding:36px 40px 0 40px;">
  <h1 class="h1" style="margin:0; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:42px; line-height:45px; color:#FF0042; text-transform:uppercase;">
    @foreach ($lines as [$text, $weight])
    <span style="font-weight:{{ $weight === 'light' ? 300 : 700 }};">{{ $text }}</span>@unless ($loop->last)<br>@endunless
    @endforeach
  </h1>
</td></tr>
