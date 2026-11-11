<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Materials;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\HeaderUtils;
use TresPontosTech\Api\DTOs\Employee\Materials\MaterialDownload;

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
                'ResponseContentDisposition' => HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_ATTACHMENT,
                    $filename,
                    $this->asciiFallback($filename),
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
     * O título do material com a extensão do arquivo ("Relatório de março.pdf"); sem título, o
     * nome guardado. Um título que já termina com a extensão não a ganha de novo.
     */
    private function filename(string $title, Media $media): string
    {
        $base = str($this->withoutForbiddenCharacters($title))->limit(120, '')->trim()->toString();
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);

        if ($base === '') {
            return $this->withoutForbiddenCharacters($media->file_name);
        }

        if ($extension === '' || str($base)->lower()->endsWith('.' . str($extension)->lower())) {
            return $base;
        }

        return $base . '.' . $extension;
    }

    /**
     * Sem os caracteres que um sistema de arquivos recusa, inclusive as barras, que o
     * `Content-Disposition` também recusa.
     */
    private function withoutForbiddenCharacters(string $name): string
    {
        return str($name)->replaceMatches('#[\\\\/:*?"<>|\x00-\x1F\x7F]+#u', ' ')->squish()->toString();
    }

    /**
     * O `filename` em ASCII do header, para quem não lê o `filename*` em UTF-8. O Symfony recusa
     * `%` nele (as barras já saíram do nome). Um nome que fica vazio em ASCII (só emoji, por
     * exemplo) cairia no UTF-8, que ele também recusa; aí vale um nome genérico com a extensão.
     */
    private function asciiFallback(string $filename): string
    {
        $base = str(pathinfo($filename, PATHINFO_FILENAME))->ascii()->remove('%')->trim()->toString();
        $extension = str(pathinfo($filename, PATHINFO_EXTENSION))->ascii()->remove('%')->toString();

        return ($base === '' ? 'material' : $base) . ($extension === '' ? '' : '.' . $extension);
    }
}
