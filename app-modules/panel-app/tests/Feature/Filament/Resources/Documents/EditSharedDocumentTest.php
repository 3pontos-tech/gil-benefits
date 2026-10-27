<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;
use TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\Pages\EditSharedDocument;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

function shareWith(Document $document, User $employee): void
{
    DocumentShare::factory()
        ->for($document)
        ->for($employee, 'employee')
        ->for($document->documentable, 'consultant')
        ->active()
        ->create();
}

it('lets the employee edit and delete their own upload', function (): void {
    $document = Document::factory()->forUser($this->employee)->active()->withFile()->create();

    livewire(EditSharedDocument::class, ['record' => $document->getKey()])
        ->assertOk()
        ->fillForm(['title' => 'Renamed by owner'])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Document::class, ['id' => $document->getKey(), 'title' => 'Renamed by owner']);

    livewire(EditSharedDocument::class, ['record' => $document->getKey()])
        ->callAction('delete');

    assertSoftDeleted(Document::class, ['id' => $document->getKey()]);
});

it('forbids the employee to edit a consultant material shared with them', function (): void {
    $document = Document::factory()->forConsultant()->active()->withLink()->create();
    shareWith($document, $this->employee);

    livewire(EditSharedDocument::class, ['record' => $document->getKey()])
        ->assertForbidden();

    assertDatabaseHas(Document::class, ['id' => $document->getKey(), 'title' => $document->title]);
});

it('hides a material that is neither theirs nor shared with them', function (): void {
    $document = Document::factory()->forConsultant()->active()->withLink()->create();

    expect(fn () => livewire(EditSharedDocument::class, ['record' => $document->getKey()]))
        ->toThrow(ModelNotFoundException::class);
});

it('hides another employee upload', function (): void {
    $anotherEmployee = User::factory()->employee()->create();
    $document = Document::factory()->forUser($anotherEmployee)->active()->withLink()->create();

    expect(fn () => livewire(EditSharedDocument::class, ['record' => $document->getKey()]))
        ->toThrow(ModelNotFoundException::class);
});

it('hides a material whose share with them was deactivated', function (): void {
    $document = Document::factory()->forConsultant()->active()->withLink()->create();
    DocumentShare::factory()
        ->for($document)
        ->for($this->employee, 'employee')
        ->for($document->documentable, 'consultant')
        ->notActive()
        ->create();

    expect(fn () => livewire(EditSharedDocument::class, ['record' => $document->getKey()]))
        ->toThrow(ModelNotFoundException::class);
});
