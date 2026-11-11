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
     * sem arquivo, `link` se houver link. Um arquivo de tipo desconhecido, ou nada, deixa o tipo
     * vazio, nunca um `link` sem link.
     *
     * As mídias são lidas de novo do banco: a lista carregada antes de o Filament salvar o
     * arquivo ainda está vazia, e era por ela que todo arquivo enviado pelo painel virava link.
     *
     * `$chosenAsLink` é a escolha "Link" do consultor no painel. O upload some só no navegador e
     * o arquivo escolhido antes ainda chega, então o arquivo sai e o link fica.
     */
    public function handle(Document $document, bool $chosenAsLink = false): void
    {
        if ($chosenAsLink) {
            $document->unsetRelation('media')->clearMediaCollection('documents');
        }

        $media = $document->unsetRelation('media')->getFirstMedia('documents');

        if ($media instanceof Media) {
            $document->update([
                'type' => DocumentExtensionTypeEnum::fromFile($media->mime_type, $media->extension),
                'link' => null,
            ]);

            return;
        }

        $document->update(['type' => filled($document->link) ? DocumentExtensionTypeEnum::Link : null]);
    }
}
