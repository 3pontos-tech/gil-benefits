<?php

declare(strict_types=1);

use TresPontosTech\App\Filament\Resources\SharedDocuments\Pages\ListSharedDocuments;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('should render', function (): void {
    livewire(ListSharedDocuments::class)
        ->assertOk();
});

it('should render only shared documents', function (): void {
    $document = Document::factory()->create(['active' => true]);
    $sharedDocument = DocumentShare::factory(3)
        ->create([
            'document_id' => $document->id,
            'employee_id' => $this->employee->id,
            'consultant_id' => $document->consultant_id,
            'active' => true,
        ]);

    livewire(ListSharedDocuments::class)
        ->assertOk()
        ->assertCanSeeTableRecords($sharedDocument);
})->todo();
