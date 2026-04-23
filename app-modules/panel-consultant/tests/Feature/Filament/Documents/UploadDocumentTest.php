<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Consultants\Filament\Resources\Documents\Pages\CreateDocument;
use TresPontosTech\Consultants\Models\Document;

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
        ->and($media->name)->toBe('image');
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
