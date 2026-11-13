<?php

declare(strict_types=1);

namespace App\Support\Mail;

use Illuminate\Support\HtmlString;

/**
 * Texto corrido dos e-mails. Os textos vêm dos arquivos de tradução com o mesmo destaque
 * `**trecho**` que os e-mails em markdown usavam: tudo é escapado antes e só então o destaque
 * vira negrito, então o nome de uma pessoa ou a observação de um chamado nunca viram HTML.
 */
final class MailText
{
    /**
     * Negrito sobre o fundo branco.
     */
    public const string STRONG = '#2B2B2B';

    /**
     * Negrito sobre os blocos escuros.
     */
    public const string ACCENT = '#FF7C27';

    public static function inline(?string $text, string $strongColor = self::STRONG): HtmlString
    {
        $html = preg_replace(
            '/\*\*(.+?)\*\*/s',
            sprintf('<strong style="color:%s;">$1</strong>', $strongColor),
            e((string) $text),
        );

        return new HtmlString(nl2br((string) $html, false));
    }
}
