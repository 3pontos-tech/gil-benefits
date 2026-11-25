<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * O nome com que um arquivo privado chega ao app pelo link temporário: os materiais e os anexos
 * dos chamados baixam com o nome que a pessoa vê (`Content-Disposition`), para o app guardar e
 * compartilhar o arquivo, nunca o link.
 */
final class DownloadFilename
{
    /**
     * Sem os caracteres que um sistema de arquivos recusa, inclusive as barras, que o
     * `Content-Disposition` também recusa.
     */
    public static function clean(string $name): string
    {
        return str($name)->replaceMatches('#[\\\\/:*?"<>|\x00-\x1F\x7F]+#u', ' ')->squish()->toString();
    }

    /**
     * O nome do arquivo como a pessoa o enviou ("relatório de novembro.pdf"): a biblioteca de mídia
     * grava o arquivo com espaços trocados por hífens, mas guarda o nome original sem a extensão.
     */
    public static function ofMedia(Media $media): string
    {
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $name = self::clean((string) $media->name);

        if ($name === '') {
            return self::clean($media->file_name);
        }

        return $extension === '' ? $name : $name . '.' . $extension;
    }

    /**
     * O header `attachment` com o nome em UTF-8 (`filename*`) e um nome em ASCII para quem não o
     * lê. Um nome que fica vazio em ASCII (só emoji, por exemplo) vira `$fallbackBase` com a
     * extensão.
     */
    public static function disposition(string $filename, string $fallbackBase): string
    {
        return HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename,
            self::asciiFallback($filename, $fallbackBase),
        );
    }

    /**
     * O Symfony recusa `%` no nome ASCII (as barras já saíram com `clean`), e cairia no UTF-8,
     * que ele também recusa ali.
     */
    private static function asciiFallback(string $filename, string $fallbackBase): string
    {
        $base = str(pathinfo($filename, PATHINFO_FILENAME))->ascii()->remove('%')->trim()->toString();
        $extension = str(pathinfo($filename, PATHINFO_EXTENSION))->ascii()->remove('%')->toString();

        return ($base === '' ? $fallbackBase : $base) . ($extension === '' ? '' : '.' . $extension);
    }
}
