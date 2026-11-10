<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config(['services.hero_video.enabled' => true]);

    Storage::fake('site-assets')->buildTemporaryUrlsUsing(
        fn (string $path, DateTimeInterface $expiration): string => 'https://signed.test/' . $path . '?expires=' . $expiration->getTimestamp(),
    );
});

it('redirects each rendition to a link signed for six hours', function (string $rendition, string $path): void {
    $this->freezeTime();

    $this->get(route('site.hero-video', $rendition))
        ->assertRedirect('https://signed.test/' . $path . '?expires=' . now()->addHours(6)->getTimestamp());
})->with([
    'desktop' => ['hd', 'hero/flamma-comercial-v2-1080p.mp4'],
    'mobile' => ['sd', 'hero/flamma-comercial-v2-720p.mp4'],
]);

it('returns not found for a rendition that does not exist', function (): void {
    $this->get('/midia/hero/4k')->assertNotFound();
});

it('returns not found when the site assets bucket is not configured', function (): void {
    config(['services.hero_video.enabled' => false]);

    $this->get(route('site.hero-video', 'hd'))->assertNotFound();
});
