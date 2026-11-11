<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\PanelConsultant\Filament\Resources\Documents\Pages\CreateDocument;
use TresPontosTech\PanelConsultant\Filament\Resources\Documents\Pages\EditDocument;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->consultant = actingAsConsultant();
});

it('should render', function (): void {
    livewire(CreateDocument::class)
        ->assertOk();
});

it('should be able to upload a document', function (): void {
    Storage::fake('public');
    $image = UploadedFile::fake()->image('image.jpg');

    livewire(CreateDocument::class)
        ->assertOk()
        ->fillForm([
            'title' => 'document_title',
            'active' => true,
            'documents' => $image,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Document::class, [
        'title' => 'document_title',
        'active' => true,
        'documentable_id' => $this->consultant->getKey(),
        'documentable_type' => 'consultants',
    ]);

    assertDatabaseCount(Media::class, 1);
    $document = Document::query()
        ->where('documents.documentable_id', $this->consultant->getKey())
        ->first();

    $media = $document->getFirstMedia('documents');
    expect($media->model_id)->toBe($document->getKey())
        ->and($media->name)->toBe('image')
        ->and($document->type)->toBe(DocumentExtensionTypeEnum::JPG);
});

test('document must be required when it is on document tab', function (): void {
    livewire(CreateDocument::class)
        ->assertOk()
        ->fillForm([
            'title' => 'document_title',
            'active' => true,
            '_document_type' => 'file',
        ])
        ->call('create')
        ->assertHasFormErrors(['documents' => 'required']);
});

test('link must be required when it is on link tab', function (): void {
    livewire(CreateDocument::class)
        ->assertOk()
        ->fillForm([
            'title' => 'document_title',
            'active' => true,
            '_document_type' => 'link',
        ])
        ->call('create')
        ->assertHasFormErrors(['link' => 'required']);
});

it('keeps the type of the file sent, and a link as a link', function (): void {
    Storage::fake('public');

    livewire(CreateDocument::class)
        ->fillForm([
            'title' => 'Extrato',
            'active' => true,
            'documents' => UploadedFile::fake()->createWithContent('extrato.pdf', "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n"),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    livewire(CreateDocument::class)
        ->fillForm([
            'title' => 'Artigo',
            'active' => true,
            '_document_type' => 'link',
            'link' => 'https://example.com/artigo',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Document::query()->where('title', 'Extrato')->sole()->type)->toBe(DocumentExtensionTypeEnum::PDF)
        ->and(Document::query()->where('title', 'Artigo')->sole()->type)->toBe(DocumentExtensionTypeEnum::Link);
});

/**
 * O upload some só no navegador quando o consultor escolhe "Link": o arquivo escolhido antes
 * ainda chega ao servidor, e a escolha do consultor é que vale.
 */
it('keeps the link when the consultant switches to link after picking a file', function (): void {
    Storage::fake('public');

    livewire(CreateDocument::class)
        ->fillForm([
            'title' => 'Aula',
            'active' => true,
            'documents' => UploadedFile::fake()->createWithContent('extrato.pdf', "%PDF-1.4\n%%EOF\n"),
            '_document_type' => 'link',
            'link' => 'https://example.com/aula',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $document = Document::query()->where('title', 'Aula')->sole();

    expect($document)
        ->type->toBe(DocumentExtensionTypeEnum::Link)
        ->link->toBe('https://example.com/aula')
        ->and($document->media()->count())->toBe(0);
});

it('turns a file into a link when the consultant switches it on edit', function (): void {
    Storage::fake('public');
    livewire(CreateDocument::class)
        ->fillForm([
            'title' => 'Extrato',
            'active' => true,
            'documents' => UploadedFile::fake()->createWithContent('extrato.pdf', "%PDF-1.4\n%%EOF\n"),
        ])
        ->call('create')
        ->assertHasNoFormErrors();
    $document = Document::query()->where('title', 'Extrato')->sole();

    livewire(EditDocument::class, ['record' => $document->getRouteKey()])
        ->fillForm(['_document_type' => 'link', 'link' => 'https://example.com/aula'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($document->fresh())
        ->type->toBe(DocumentExtensionTypeEnum::Link)
        ->link->toBe('https://example.com/aula')
        ->and($document->media()->count())->toBe(0);
});
