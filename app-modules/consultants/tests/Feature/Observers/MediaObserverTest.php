<?php

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Observers\MediaObserver;

describe('MediaObserver', function (): void {
    it('updates document type for a valid extension', function (string $fileName, DocumentExtensionTypeEnum $expected): void {
        $document = Document::query()->create(['title' => 'test']);

        $media = new Media;
        $media->model_type = 'documents';
        $media->collection_name = 'documents';
        $media->file_name = $fileName;
        $media->model_id = $document->getKey();

        (new MediaObserver)->created($media);

        expect($document->fresh()->type)->toBe($expected);
    })->with([
        'pdf' => ['document.pdf',  DocumentExtensionTypeEnum::PDF],
        'docx' => ['document.docx', DocumentExtensionTypeEnum::Docx],
        'jpg' => ['image.jpg',     DocumentExtensionTypeEnum::JPG],
        'xlsx' => ['sheet.xlsx',    DocumentExtensionTypeEnum::XLSX],
    ]);

    it('does not update type for uppercase extension', function (): void {
        $document = Document::query()->create(['title' => 'test']);

        $media = new Media;
        $media->model_type = 'documents';
        $media->collection_name = 'documents';
        $media->file_name = 'document.PDF';
        $media->model_id = $document->getKey();

        (new MediaObserver)->created($media);

        expect($document->fresh()->type)->toBeNull();
    });

    it('does not change type for an unrecognized extension', function (): void {
        $document = Document::query()->create(['title' => 'test']);

        $media = new Media;
        $media->model_type = 'documents';
        $media->collection_name = 'documents';
        $media->file_name = 'malware.exe';
        $media->model_id = $document->getKey();

        (new MediaObserver)->created($media);

        expect($document->fresh()->type)->toBeNull();
    });

    it('ignores media with a different model_type', function (): void {
        $document = Document::query()->create(['title' => 'test']);

        $media = new Media;
        $media->model_type = 'consultants';
        $media->collection_name = 'documents';
        $media->file_name = 'document.pdf';
        $media->model_id = $document->getKey();

        (new MediaObserver)->created($media);

        expect($document->fresh()->type)->toBeNull();
    });

    it('ignores media from a different collection', function (): void {
        $document = Document::query()->create(['title' => 'test']);

        $media = new Media;
        $media->model_type = 'documents';
        $media->collection_name = 'avatar';
        $media->file_name = 'document.pdf';
        $media->model_id = $document->getKey();

        (new MediaObserver)->created($media);

        expect($document->fresh()->type)->toBeNull();
    });
});
