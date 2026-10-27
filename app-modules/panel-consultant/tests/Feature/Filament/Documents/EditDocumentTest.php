<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\PanelConsultant\Filament\Resources\Documents\Pages\EditDocument;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->consultant = actingAsConsultant();
});

it('lets the consultant edit their own material', function (): void {
    $document = Document::factory()->forConsultant($this->consultant)->active()->withLink()->create();

    livewire(EditDocument::class, ['record' => $document->getKey()])
        ->assertOk()
        ->fillForm(['title' => 'Renamed by consultant'])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Document::class, ['id' => $document->getKey(), 'title' => 'Renamed by consultant']);
});

it('hides another consultant material', function (): void {
    $document = Document::factory()->forConsultant()->active()->withLink()->create();

    expect(fn () => livewire(EditDocument::class, ['record' => $document->getKey()]))
        ->toThrow(ModelNotFoundException::class);
});
