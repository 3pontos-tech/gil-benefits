@props(['area'])

@php
    /** @var \App\Enums\SupportArea $area */
    $supportPhone = preg_replace('/\D/', '', (string) config('services.whatsapp.support_phone'));
    $isInternalTeam = auth()->user()?->isInternalTeam() ?? false;
@endphp

@if (filled($supportPhone) && ! $isInternalTeam)
    <a
        href="https://wa.me/{{ $supportPhone }}?text={{ rawurlencode($area->whatsappMessage()) }}"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Falar com o Suporte pelo WhatsApp (abre em nova aba)"
        data-support-whatsapp="{{ $area->value }}"
        @class([
            'fixed bottom-[max(1rem,env(safe-area-inset-bottom))] start-[max(1rem,env(safe-area-inset-left))] z-20',
            'inline-flex min-h-11 min-w-11 items-center justify-center gap-2 rounded-full px-3 py-2.5 sm:px-4',
            'bg-[#25D366] text-sm font-semibold text-[#111B21] shadow-lg ring-1 ring-black/10 transition hover:bg-[#1FBF5A]',
            'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#111B21] dark:focus-visible:outline-white',
        ])
    >
        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="currentColor" class="size-6 shrink-0">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
        </svg>

        <span class="max-[22.5rem]:sr-only">Suporte</span>
    </a>

    @once
        <style>
            .fi-body:has([data-support-whatsapp]) .fi-sidebar-nav {
                padding-bottom: calc(var(--spacing) * 20);
            }
        </style>

        <script>
            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-support-whatsapp]');

                if (! link || ! window.posthog || typeof window.posthog.capture !== 'function') {
                    return;
                }

                try {
                    window.posthog.capture('suporte_whatsapp_clicado', {
                        area: link.dataset.supportWhatsapp,
                        button_location: 'floating_bottom_left',
                        product_name: 'Flamma',
                    });
                } catch (error) {}
            });
        </script>
    @endonce
@endif
