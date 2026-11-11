<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Consultants\Actions\SyncDocumentTypeAction;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;

/**
 * @param  array<string, mixed>  $attributes
 */
function consultantDocument(array $attributes): Document
{
    return Document::factory()->forConsultant(Consultant::factory()->create())->create(['link' => null, ...$attributes]);
}

function attachDocumentFile(Document $document, string $name, string $content): void
{
    Document::query()->findOrFail($document->getKey())
        ->addMedia(UploadedFile::fake()->createWithContent($name, $content))
        ->toMediaCollection('documents');
}

function minimalPdf(): string
{
    return "%PDF-1.4\n%%EOF\n";
}

beforeEach(function (): void {
    Storage::fake('public');
    config()->set('media-library.disk_name', 'public');
});

/**
 * Começa com link para a leitura da lista carregada fazer diferença: o `MediaObserver` já grava
 * o tipo do arquivo, mas só a ação tira o link.
 */
it('takes the type from the file and drops the link, even when the loaded media list is stale', function (): void {
    $document = consultantDocument(['title' => 'Planilha', 'type' => DocumentExtensionTypeEnum::Link, 'link' => 'https://example.com/planilha']);
    $document->load('media');
    attachDocumentFile($document, 'orcamento.PDF', minimalPdf());

    resolve(SyncDocumentTypeAction::class)->handle($document);

    expect($document->fresh())
        ->type->toBe(DocumentExtensionTypeEnum::PDF)
        ->link->toBeNull();
});

it('keeps a document without a file as a link', function (): void {
    $document = consultantDocument(['title' => 'Artigo', 'type' => null, 'link' => 'https://example.com/artigo']);

    resolve(SyncDocumentTypeAction::class)->handle($document);

    expect($document->fresh()->type)->toBe(DocumentExtensionTypeEnum::Link);
});

it('keeps the link the consultant chose, dropping a file picked before', function (): void {
    $document = consultantDocument(['title' => 'Aula', 'link' => 'https://example.com/aula']);
    $document->load('media');
    attachDocumentFile($document, 'extrato.pdf', minimalPdf());

    resolve(SyncDocumentTypeAction::class)->handle($document, chosenAsLink: true);

    expect($document->fresh())
        ->type->toBe(DocumentExtensionTypeEnum::Link)
        ->link->toBe('https://example.com/aula')
        ->and($document->media()->count())->toBe(0);
});

it('leaves the type empty, never a link without a link', function (string $case): void {
    $document = consultantDocument(['title' => 'Arquivo', 'type' => DocumentExtensionTypeEnum::Link]);

    if ($case === 'unknown file') {
        attachDocumentFile($document, 'arquivo.zip', "PK\x03\x04");
    }

    resolve(SyncDocumentTypeAction::class)->handle($document);

    expect($document->fresh())
        ->type->toBeNull()
        ->link->toBeNull();
})->with(['unknown file', 'no file and no link']);

/**
 * O rastro do bug: `type = link`, sem link, com arquivo. Um documento com arquivo e link é a
 * escolha do consultor e fica como está.
 */
it('repairs only the documents the panel saved as links', function (): void {
    $wrong = consultantDocument(['title' => 'Extrato']);
    attachDocumentFile($wrong, 'extrato.pdf', minimalPdf());
    $chosenLink = consultantDocument(['title' => 'Aula', 'link' => 'https://example.com/aula']);
    attachDocumentFile($chosenLink, 'roteiro.pdf', minimalPdf());
    $unknown = consultantDocument(['title' => 'Compactado']);
    attachDocumentFile($unknown, 'pacote.zip', "PK\x03\x04");
    Document::query()->whereKey([$wrong->getKey(), $chosenLink->getKey(), $unknown->getKey()])->update(['type' => DocumentExtensionTypeEnum::Link]);
    $updatedAt = DB::table('documents')->where('id', $wrong->getKey())->value('updated_at');
    $this->travel(1)->hour();

    (require base_path('app-modules/consultants/database/migrations/2026_11_11_120000_sync_document_types_from_their_files.php'))->up();

    expect($wrong->fresh()->type)->toBe(DocumentExtensionTypeEnum::PDF)
        ->and(DB::table('documents')->where('id', $wrong->getKey())->value('updated_at'))->toBe($updatedAt)
        ->and($chosenLink->fresh())
        ->type->toBe(DocumentExtensionTypeEnum::Link)
        ->link->toBe('https://example.com/aula')
        ->and($unknown->fresh()->type)->toBeNull();
});
