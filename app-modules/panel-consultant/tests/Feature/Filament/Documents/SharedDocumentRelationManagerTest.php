<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Livewire\Features\SupportTesting\Testable;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Filament\Resources\Documents\Pages\EditDocument;
use TresPontosTech\Consultants\Filament\Resources\Documents\RelationManagers\SharedDocumentRelationManager;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->consultant = actingAsConsultant();

    $this->document = Document::factory()
        ->forConsultant($this->consultant)
        ->create();

    $this->employee = User::factory()->create();

    Appointment::factory()
        ->for($this->employee, 'user')
        ->for($this->consultant, 'consultant')
        ->create();

    $this->share = DocumentShare::factory()
        ->for($this->document, 'document')
        ->for($this->consultant, 'consultant')
        ->for($this->employee, 'employee')
        ->active()
        ->create();
});

function mountRelationManager(Document $document): Testable
{
    return livewire(SharedDocumentRelationManager::class, [
        'ownerRecord' => $document,
        'pageClass' => EditDocument::class,
    ]);
}

it('renders and lists the document shares', function (): void {
    mountRelationManager($this->document)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->share]);
});

it('deactivates an active share through the active action', function (): void {
    mountRelationManager($this->document)
        ->callTableAction('active', $this->share)
        ->assertHasNoTableActionErrors();

    expect($this->share->refresh()->isActive())->toBeFalse();
});

it('activates an inactive share through the active action', function (): void {
    $this->share->deactivate();

    mountRelationManager($this->document)
        ->callTableAction('active', $this->share->refresh())
        ->assertHasNoTableActionErrors();

    expect($this->share->refresh()->isActive())->toBeTrue();
});

it('deletes a share via the delete row action', function (): void {
    mountRelationManager($this->document)
        ->callTableAction(DeleteAction::class, $this->share)
        ->assertHasNoTableActionErrors();

    assertDatabaseMissing(DocumentShare::class, [
        'id' => $this->share->getKey(),
    ]);
});

it('bulk deletes selected shares', function (): void {
    $anotherEmployee = User::factory()->create();
    Appointment::factory()
        ->for($anotherEmployee, 'user')
        ->for($this->consultant, 'consultant')
        ->create();

    $secondShare = DocumentShare::factory()
        ->for($this->document, 'document')
        ->for($this->consultant, 'consultant')
        ->for($anotherEmployee, 'employee')
        ->active()
        ->create();

    mountRelationManager($this->document)
        ->callTableBulkAction(DeleteBulkAction::class, [$this->share, $secondShare]);

    assertDatabaseMissing(DocumentShare::class, ['id' => $this->share->getKey()]);
    assertDatabaseMissing(DocumentShare::class, ['id' => $secondShare->getKey()]);
});

it('only lists shares that belong to the document being viewed', function (): void {
    $otherDocument = Document::factory()->forConsultant($this->consultant)->create();

    $otherShare = DocumentShare::factory()
        ->for($otherDocument, 'document')
        ->for($this->consultant, 'consultant')
        ->for($this->employee, 'employee')
        ->active()
        ->create();

    mountRelationManager($this->document)
        ->assertCanSeeTableRecords([$this->share])
        ->assertCanNotSeeTableRecords([$otherShare]);
});
