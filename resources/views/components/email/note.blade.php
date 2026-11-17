{{-- Aviso dentro do x-email.infobox: rótulo em laranja e texto em branco. --}}
@props(['label' => null])
<p style="margin:0 0 10px 0; font-family:'Space Grotesk', Arial, Helvetica, sans-serif; font-size:14px; line-height:21px; color:#FFFFFF;">@if (filled($label))<strong style="color:#FF7C27;">{{ $label }}</strong> @endif{{ $slot }}</p>
