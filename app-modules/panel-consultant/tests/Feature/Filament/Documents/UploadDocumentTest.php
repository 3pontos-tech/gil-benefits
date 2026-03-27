<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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
        'consultant_id' => $this->consultant->getKey(),
    ]);

    assertDatabaseCount(Media::class, 1);
    $document = Document::query()
        ->where('documents.consultant_id', $this->consultant->getKey())
        ->first();

    $media = $document->getFirstMedia('documents');
    expect($media->model_id)->toBe($document->getKey())
        ->and($media->name)->toBe('image');
});
