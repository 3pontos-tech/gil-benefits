{{--
    Vídeo comercial do hero (Figma 9118:463 no desktop, 9153:442 no mobile).

    A página carrega só o poster, que é o quadro 0 do vídeo (o mesmo do Figma). O <video>
    nasce sem src, então nada do arquivo é baixado antes do clique. No clique, o handler
    define o src e chama play() na mesma chamada: é o que faz o iPhone aceitar tocar com
    som. O src é a rota site.hero-video, com URL relativa, que redireciona para um link
    assinado do bucket privado; assim o HTML não depende de domínio.

    A partir de xl toca a versão 1080p (a caixa chega a 946px); abaixo, a de 720p. Abaixo
    de sm o vídeo abre em tela cheia, porque a legenda gravada na imagem fica com ~5px num
    player de 288px: no Android pelo requestFullscreen, no iPhone pela retirada do
    playsinline, que faz o Safari abrir o player nativo em tela cheia.

    Sem o bucket configurado (dev local, testes), sobra só o poster.
--}}

@php
    $hasVideo = config()->boolean('services.hero_video.enabled');
@endphp

<div
    {{ $attributes->class(['relative aspect-video w-full overflow-hidden rounded bg-dark']) }}
    @if ($hasVideo)
        x-data="{
            playing: false,
            play() {
                const video = this.$refs.video;

                if (! window.matchMedia('(min-width: 40rem)').matches) {
                    video.removeAttribute('playsinline');

                    if (video.requestFullscreen) {
                        video.requestFullscreen().catch(() => {});
                    }
                }

                video.src = window.matchMedia('(min-width: 80rem)').matches
                    ? @js(route('site.hero-video', 'hd', false))
                    : @js(route('site.hero-video', 'sd', false));

                this.playing = true;
                video.play().catch(() => this.stop());
                this.$nextTick(() => video.focus({ preventScroll: true }));
            },
            stop() {
                this.playing = false;

                if (document.fullscreenElement) {
                    document.exitFullscreen().catch(() => {});
                }
            },
        }"
    @endif
>
    <img
        src="{{ asset('img/home/hero-video-poster-960.webp') }}"
        srcset="{{ asset('img/home/hero-video-poster-960.webp') }} 960w, {{ asset('img/home/hero-video-poster-1892.webp') }} 1892w"
        sizes="(min-width: 80rem) 946px, (min-width: 49rem) 720px, (min-width: 40rem) calc(100vw - 4rem), calc(100vw - 2rem)"
        width="960"
        height="540"
        alt="Porta-voz do Flamma sentada numa poltrona ao ar livre, no quadro de abertura do vídeo comercial"
        class="size-full object-cover"
        fetchpriority="high"
        decoding="async"
        @if ($hasVideo) x-show="! playing" @endif
    >

    @if ($hasVideo)
        <video
            x-ref="video"
            x-show="playing"
            x-cloak
            x-on:error="stop()"
            class="absolute inset-0 size-full bg-dark"
            aria-label="Vídeo comercial do Flamma"
            controls
            playsinline
            preload="none"
        ></video>

        <button
            type="button"
            x-show="! playing"
            x-on:click="play()"
            class="group absolute inset-0 flex items-center justify-center focus:outline-none"
            aria-label="Assistir ao vídeo do Flamma"
        >
            <span class="flex size-14 items-center justify-center bg-icon-light text-brand-primary shadow-lg transition-transform duration-300 group-focus-visible:outline-2 group-focus-visible:outline-offset-4 group-focus-visible:outline-icon-light motion-safe:group-hover:scale-105 xl:size-[72px]">
                <x-heroicon-s-play class="size-7 xl:size-9" aria-hidden="true" />
            </span>
        </button>
    @endif
</div>
