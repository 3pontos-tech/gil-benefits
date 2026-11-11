<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Actions;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Models\Document;

final readonly class SyncDocumentTypeAction
{
    /**
     * O tipo vem do que o documento tem de fato: com arquivo, o tipo do arquivo (e o link sai);
     * sem arquivo e com link, `link`. As mídias são lidas de novo do banco: a lista carregada
     * antes de o Filament salvar o arquivo ainda está vazia, e era por ela que todo arquivo
     * enviado pelo painel virava link.
     */
    public function handle(Document $document): void
    {
        $media = $document->media()->where('collection_name', 'documents')->orderBy('order_column')->first();

        if ($media instanceof Media) {
            $document->update([
                'type' => DocumentExtensionTypeEnum::fromFile($media->mime_type, $media->extension) ?? $document->type,
                'link' => null,
            ]);

            return;
        }

        if (filled($document->link)) {
            $document->update(['type' => DocumentExtensionTypeEnum::Link]);
        }
    }
}
