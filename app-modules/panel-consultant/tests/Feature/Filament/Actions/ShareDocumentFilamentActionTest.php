<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Filament\Actions\ShareDocumentFilamentAction;
use TresPontosTech\Consultants\Filament\Resources\Documents\Pages\EditDocument;
use TresPontosTech\Consultants\Filament\Resources\Documents\Pages\ListDocuments;
use TresPontosTech\Consultants\Filament\Resources\Documents\RelationManagers\SharedDocumentRelationManager;
use TresPontosTech\Consultants\Mail\DocumentSharedMail;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
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
});

it('queues a DocumentSharedMail to the employee when sharing via documents table', function (): void {
    Mail::fake();

    livewire(ListDocuments::class)
        ->callAction(
            TestAction::make(ShareDocumentFilamentAction::getDefaultName())->table($this->document),
            data: ['employee_id' => $this->employee->getKey()],
        )
        ->assertHasNoActionErrors();

    assertDatabaseHas(DocumentShare::class, [
        'document_id' => $this->document->getKey(),
        'employee_id' => $this->employee->getKey(),
        'consultant_id' => $this->consultant->getKey(),
    ]);

    Mail::assertQueued(
        DocumentSharedMail::class,
        fn (DocumentSharedMail $mail): bool => $mail->hasTo($this->employee->email)
            && $mail->document->is($this->document)
            && $mail->employee->is($this->employee)
    );
});

it('shares a document through the relation manager header action', function (): void {
    Mail::fake();

    livewire(SharedDocumentRelationManager::class, [
        'ownerRecord' => $this->document,
        'pageClass' => EditDocument::class,
    ])
        ->callAction(
            TestAction::make(ShareDocumentFilamentAction::getDefaultName())->table(),
            data: ['employee_id' => $this->employee->getKey()],
        )
        ->assertHasNoActionErrors();

    assertDatabaseHas(DocumentShare::class, [
        'document_id' => $this->document->getKey(),
        'employee_id' => $this->employee->getKey(),
        'consultant_id' => $this->consultant->getKey(),
    ]);

    Mail::assertQueued(DocumentSharedMail::class);
});

it('halts and does not send mail when document was already shared with the same employee', function (): void {
    Mail::fake();

    DocumentShare::factory()
        ->for($this->document, 'document')
        ->for($this->consultant, 'consultant')
        ->for($this->employee, 'employee')
        ->active()
        ->create();

    livewire(ListDocuments::class)
        ->callAction(
            TestAction::make(ShareDocumentFilamentAction::getDefaultName())->table($this->document),
            data: ['employee_id' => $this->employee->getKey()],
        );

    assertDatabaseCount(DocumentShare::class, 1);
    Mail::assertNothingQueued();
});

it('rejects sharing with a user that is not a client of the consultant', function (): void {
    Mail::fake();

    $outsider = User::factory()->create();

    livewire(ListDocuments::class)
        ->callAction(
            TestAction::make(ShareDocumentFilamentAction::getDefaultName())->table($this->document),
            data: ['employee_id' => $outsider->getKey()],
        )
        ->assertHasActionErrors(['employee_id']);

    assertDatabaseCount(DocumentShare::class, 0);
    Mail::assertNothingQueued();
});

it('requires the employee_id field', function (): void {
    livewire(ListDocuments::class)
        ->callAction(
            TestAction::make(ShareDocumentFilamentAction::getDefaultName())->table($this->document),
            data: [],
        )
        ->assertHasActionErrors(['employee_id' => 'required']);

    assertDatabaseCount(DocumentShare::class, 0);
});
