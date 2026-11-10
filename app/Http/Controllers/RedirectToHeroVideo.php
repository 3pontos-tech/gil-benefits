<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Entrega o vídeo do hero sem tornar o bucket público e sem depender de domínio: o HTML
 * aponta para esta rota com URL relativa, e ela redireciona para um link assinado do
 * bucket privado `site-assets`, gerado na hora do play.
 *
 * O link vale 6 horas porque o player pede trechos do arquivo ao longo da reprodução:
 * quem pausa e volta depois não pode encontrar o link vencido no meio do vídeo.
 */
class RedirectToHeroVideo extends Controller
{
    public function __invoke(string $rendition): RedirectResponse
    {
        abort_unless(config()->boolean('services.hero_video.enabled'), 404);

        $path = config()->string('services.hero_video.renditions.' . $rendition);

        return redirect()->away(Storage::disk('site-assets')->temporaryUrl($path, now()->addHours(6)));
    }
}
