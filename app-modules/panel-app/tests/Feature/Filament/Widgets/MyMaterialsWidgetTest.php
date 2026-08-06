<?php

declare(strict_types=1);

use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\Widgets\MyMaterialsWidget;

use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

function ownDocument(): Document
{
    return Document::factory()->withFile()->create([
        'documentable_type' => test()->employee->getMorphClass(),
        'documentable_id' => test()->employee->getKey(),
        'active' => true,
    ]);
}

it('lists only the employee own documents', function (): void {
    $mine = ownDocument();
    $someoneElses = Document::factory()->forConsultant()->active()->withFile()->create();

    livewire(MyMaterialsWidget::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$someoneElses]);
});

it('offers access, edit and delete on an own document', function (): void {
    $mine = ownDocument();

    livewire(MyMaterialsWidget::class)
        ->assertOk()
        ->assertTableActionVisible('download-document-action', $mine)
        ->assertTableActionVisible('edit', $mine)
        ->assertTableActionVisible('delete', $mine);
});

it('deletes an own document from the grid', function (): void {
    $mine = ownDocument();

    livewire(MyMaterialsWidget::class)
        ->callTableAction('delete', $mine);

    assertSoftDeleted('documents', ['id' => $mine->getKey()]);
});
