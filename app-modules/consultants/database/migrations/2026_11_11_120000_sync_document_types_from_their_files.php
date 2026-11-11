<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use TresPontosTech\Consultants\Actions\SyncDocumentTypeAction;
use TresPontosTech\Consultants\Models\Document;

/**
 * Todo arquivo enviado pelo painel do consultor ficava com o tipo `link` (a lista de mídias
 * lida antes de o arquivo ser salvo estava vazia), e o app o mostrava como aula. Recalcula o
 * tipo de cada documento pelo que ele tem de fato.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sync = resolve(SyncDocumentTypeAction::class);

        Document::withTrashed()->chunkById(100, function (Collection $documents) use ($sync): void {
            $documents->each(fn (Document $document) => $sync->handle($document));
        });
    }

    public function down(): void
    {
        // O tipo anterior estava errado; não há o que desfazer.
    }
};
