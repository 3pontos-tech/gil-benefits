<?php

declare(strict_types=1);

use Illuminate\Support\Js;

it('renders the hero in the mobile reading order: title, media, text and call to action', function (): void {
    $this->get(route('site.home'))
        ->assertOk()
        ->assertSeeInOrder([
            'Saúde financeira também é saúde corporativa.',
            'img/home/hero-video-poster-960.webp',
            'A Flamma é uma solução de saúde financeira que ajuda empresas a cuidar do bem-estar',
            'Conheça a Flamma',
        ], false);
});

it('offers the commercial video through relative routes, without exposing where it is stored', function (): void {
    config(['services.hero_video.enabled' => true]);

    $this->get(route('site.home'))
        ->assertOk()
        ->assertSee('Assistir ao vídeo do Flamma')
        ->assertSee(Js::from(route('site.hero-video', 'hd', false))->toHtml(), false)
        ->assertSee(Js::from(route('site.hero-video', 'sd', false))->toHtml(), false)
        ->assertSee('preload="none"', false)
        ->assertDontSee('drive.google.com')
        ->assertDontSee('r2.cloudflarestorage.com');
});

it('shows only the poster when the video bucket is not configured', function (): void {
    config(['services.hero_video.enabled' => false]);

    $this->get(route('site.home'))
        ->assertOk()
        ->assertSee('img/home/hero-video-poster-960.webp', false)
        ->assertDontSee('Assistir ao vídeo do Flamma')
        ->assertDontSee('<video', false);
});

it('draws the hero asterisks with the box and stroke of the Figma nodes', function (string $type, string $viewBox): void {
    $this->blade('<x-graphism :type="$type" />', ['type' => $type])
        ->assertSee('viewBox="' . $viewBox . '"', false)
        ->assertSee('stroke-width="61.0935"', false);
})->with([
    'top corner' => ['hero-asterisk-top', '0 0 277 277'],
    'bottom corner' => ['hero-asterisk-bottom', '0 0 270 265.166'],
]);
