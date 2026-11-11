<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
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

beforeEach(function (): void {
    Storage::fake('public');
    config()->set('media-library.disk_name', 'public');
});

it('takes the type from the file, even when the loaded media list is stale', function (): void {
    $document = consultantDocument(['title' => 'Planilha', 'type' => DocumentExtensionTypeEnum::Link]);
    $document->load('media');
    Document::query()->findOrFail($document->getKey())
        ->addMedia(UploadedFile::fake()->createWithContent('orcamento.PDF', "%PDF-1.4\n%%EOF\n"))
        ->toMediaCollection('documents');

    resolve(SyncDocumentTypeAction::class)->handle($document);

    expect($document->fresh()->type)->toBe(DocumentExtensionTypeEnum::PDF);
});

it('keeps a document without a file as a link', function (): void {
    $document = consultantDocument(['title' => 'Artigo', 'type' => null, 'link' => 'https://example.com/artigo']);

    resolve(SyncDocumentTypeAction::class)->handle($document);

    expect($document->fresh()->type)->toBe(DocumentExtensionTypeEnum::Link);
});

it('repairs the documents the panel saved as links', function (): void {
    $wrong = consultantDocument(['title' => 'Extrato']);
    $wrong->addMedia(UploadedFile::fake()->createWithContent('extrato.pdf', "%PDF-1.4\n%%EOF\n"))->toMediaCollection('documents');
    Document::query()->whereKey($wrong->getKey())->update(['type' => DocumentExtensionTypeEnum::Link]);

    (require base_path('app-modules/consultants/database/migrations/2026_11_11_120000_sync_document_types_from_their_files.php'))->up();

    expect($wrong->fresh()->type)->toBe(DocumentExtensionTypeEnum::PDF);
});
