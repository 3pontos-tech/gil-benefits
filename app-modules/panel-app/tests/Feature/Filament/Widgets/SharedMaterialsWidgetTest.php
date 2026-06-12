<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use TresPontosTech\App\Filament\Resources\SharedDocuments\SharedDocumentResource;
use TresPontosTech\App\Filament\Widgets\SharedMaterialsWidget;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('lists documents shared with the employee with a download action', function (): void {
    $consultant = Consultant::factory()->create();
    $document = Document::factory()->create(['title' => 'Guia de reserva de emergência']);
    DocumentShare::factory()->create([
        'document_id' => $document->id,
        'consultant_id' => $consultant->id,
        'employee_id' => $this->employee->id,
        'active' => true,
    ]);

    livewire(SharedMaterialsWidget::class)
        ->assertSuccessful()
        ->assertSee('Materiais compartilhados')
        ->assertSee('Guia de reserva de emergência')
        ->assertSee('Download');
});

it('shows an empty state when nothing is shared', function (): void {
    livewire(SharedMaterialsWidget::class)
        ->assertSuccessful()
        ->assertSee('Nenhum material compartilhado ainda');
});

it('links to the full shared documents list', function (): void {
    $consultant = Consultant::factory()->create();
    $document = Document::factory()->create();
    DocumentShare::factory()->create([
        'document_id' => $document->id,
        'consultant_id' => $consultant->id,
        'employee_id' => $this->employee->id,
        'active' => true,
    ]);

    livewire(SharedMaterialsWidget::class)
        ->assertSuccessful()
        ->assertSee('Ver todos')
        ->assertSee(SharedDocumentResource::getUrl('index'));
});

it('labels link documents as open and points to the external url', function (): void {
    $consultant = Consultant::factory()->create();
    $document = Document::factory()
        ->withLink('https://example.com/material')
        ->create(['title' => 'Planilha de orçamento']);
    DocumentShare::factory()->create([
        'document_id' => $document->id,
        'consultant_id' => $consultant->id,
        'employee_id' => $this->employee->id,
        'active' => true,
    ]);

    livewire(SharedMaterialsWidget::class)
        ->assertSuccessful()
        ->assertSee('Abrir')
        ->assertSee('https://example.com/material');
});

it('previews only four materials and signals how many more exist', function (): void {
    $consultant = Consultant::factory()->create();

    for ($i = 1; $i <= 6; ++$i) {
        $document = Document::factory()->create(['title' => 'Material ' . $i]);
        DocumentShare::factory()->create([
            'document_id' => $document->id,
            'consultant_id' => $consultant->id,
            'employee_id' => $this->employee->id,
            'active' => true,
        ]);
    }

    livewire(SharedMaterialsWidget::class)
        ->assertSuccessful()
        ->assertSeeText('+2 outros materiais');
});

it('reuses the eager-loaded documents without lazy-loading media per item', function (): void {
    $consultant = Consultant::factory()->create();

    Document::factory()
        ->count(4)
        ->create()
        ->each(function (Document $document) use ($consultant): void {
            DocumentShare::factory()->create([
                'document_id' => $document->id,
                'consultant_id' => $consultant->id,
                'employee_id' => $this->employee->id,
                'active' => true,
            ]);
        });

    $mediaQueries = 0;
    DB::listen(function ($query) use (&$mediaQueries): void {
        if (str_contains(strtolower($query->sql), 'from "media"')) {
            ++$mediaQueries;
        }
    });

    livewire(SharedMaterialsWidget::class)->assertSuccessful();

    // Apenas o eager load de mídia; a action de download reaproveita os documentos já carregados.
    expect($mediaQueries)->toBeLessThanOrEqual(1);
});
