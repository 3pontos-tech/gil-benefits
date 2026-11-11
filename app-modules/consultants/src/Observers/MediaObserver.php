<?php

namespace TresPontosTech\Consultants\Observers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Models\Document;

class MediaObserver
{
    /**
     * O tipo de um documento que acabou de ganhar arquivo, para quem não passa pelo painel do
     * consultor (o upload pela API e o "Meus materiais"). Não mexe no link nem chama a
     * `SyncDocumentTypeAction`: ele roda no meio do save do painel, antes de a escolha "Link" do
     * consultor ser aplicada, e apagaria o link que ela manda manter.
     */
    public function created(Media $media): void
    {
        if ($media->model_type !== 'documents') {
            return;
        }

        if ($media->collection_name !== 'documents') {
            return;
        }

        $type = DocumentExtensionTypeEnum::fromFile($media->mime_type, $media->extension)?->value;

        if (! $type) {
            return;
        }

        Document::query()->where('id', $media->model_id)
            ->update(['type' => $type]);
    }
}
