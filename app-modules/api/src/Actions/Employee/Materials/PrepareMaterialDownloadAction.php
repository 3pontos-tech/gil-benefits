<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Materials;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Api\DTOs\Employee\Materials\MaterialDownload;
use TresPontosTech\Api\Support\DownloadFilename;

final readonly class PrepareMaterialDownloadAction
{
    public function __construct(private EmployeeMaterials $materials) {}

    /**
     * Um link do armazenamento que vale `api.materials.download_ttl_seconds` e já baixa o
     * arquivo com o nome que a pessoa vê no app (`Content-Disposition`), para o app guardar e
     * compartilhar o arquivo, nunca o link. Só sai para quem enxerga o material; um material
     * que é só um link (aula) não tem arquivo e responde como inexistente. A regra de quem
     * enxerga é a mesma da lista, sem os carregamentos que só a lista usa.
     */
    public function handle(User $user, string $documentId): MaterialDownload
    {
        $document = $this->materials->visibleTo($user)->without(['states', 'shares'])->whereKey($documentId)->firstOrFail();
        $media = $document->getFirstMedia('documents');

        throw_unless($media instanceof Media, ModelNotFoundException::class);

        $expiresAt = now()->addSeconds((int) config('api.materials.download_ttl_seconds'));
        $filename = $this->filename((string) $document->title, $media);

        return new MaterialDownload(
            url: $media->getTemporaryUrl($expiresAt, '', [
                'ResponseContentDisposition' => DownloadFilename::disposition($filename, 'material'),
                'ResponseContentType' => $media->mime_type,
            ]),
            filename: $filename,
            mimeType: (string) $media->mime_type,
            size: (int) $media->size,
            expiresAt: $expiresAt,
        );
    }

    /**
     * O título do material com a extensão do arquivo ("Relatório de março.pdf"); sem título, o
     * nome guardado. Um título que já termina com a extensão não a ganha de novo.
     */
    private function filename(string $title, Media $media): string
    {
        $base = str(DownloadFilename::clean($title))->limit(120, '')->trim()->toString();
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);

        if ($base === '') {
            return DownloadFilename::clean($media->file_name);
        }

        if ($extension === '' || str($base)->lower()->endsWith('.' . str($extension)->lower())) {
            return $base;
        }

        return $base . '.' . $extension;
    }
}
