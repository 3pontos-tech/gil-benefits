<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Materials;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\HeaderUtils;
use TresPontosTech\Api\DTOs\Employee\Materials\MaterialDownload;

final readonly class PrepareMaterialDownloadAction
{
    public function __construct(private FindEmployeeMaterialAction $findMaterial) {}

    /**
     * Um link do armazenamento que vale `api.materials.download_ttl_seconds` e já baixa o
     * arquivo com o nome que a pessoa vê no app (`Content-Disposition`), para o app guardar e
     * compartilhar o arquivo, nunca o link. Só sai para quem enxerga o material; um material
     * que é só um link (aula) não tem arquivo e responde como inexistente.
     */
    public function handle(User $user, string $documentId): MaterialDownload
    {
        $document = $this->findMaterial->handle($user, $documentId);
        $media = $document->getFirstMedia('documents');

        throw_unless($media instanceof Media, ModelNotFoundException::class);

        $expiresAt = now()->addSeconds((int) config('api.materials.download_ttl_seconds'));
        $filename = $this->filename((string) $document->title, $media);

        return new MaterialDownload(
            url: $media->getTemporaryUrl($expiresAt, '', [
                'ResponseContentDisposition' => HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_ATTACHMENT,
                    $filename,
                    Str::ascii($filename),
                ),
                'ResponseContentType' => $media->mime_type,
            ]),
            filename: $filename,
            mimeType: (string) $media->mime_type,
            size: (int) $media->size,
            expiresAt: $expiresAt,
        );
    }

    /**
     * O título do material com a extensão do arquivo ("Relatório de março.pdf"), sem os
     * caracteres que um sistema de arquivos recusa; sem título, o nome guardado.
     */
    private function filename(string $title, Media $media): string
    {
        $base = Str::of($title)->replaceMatches('#[\\\\/:*?"<>|\x00-\x1F]+#u', ' ')->squish()->limit(120, '')->trim()->toString();
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);

        if ($base === '') {
            return $media->file_name;
        }

        return $extension === '' ? $base : $base . '.' . $extension;
    }
}
