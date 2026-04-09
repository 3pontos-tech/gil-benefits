<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Filament\Actions\ShareDocumentFilamentAction;
use TresPontosTech\Consultants\Filament\Resources\Documents\Pages\ListDocuments;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->consultant = actingAsConsultant();
    $this->documents = Document::factory()->forConsultant($this->consultant)->count(5)->create();
});

it('should render', function (): void {
    livewire(ListDocuments::class)
        ->assertOk();
});

it('should be list only the consultant documents', function (): void {
    $documentsUploadedByAnotherConsultant = Document::factory()->count(5)->create();
    livewire(ListDocuments::class)
        ->assertOk()
        ->assertCanSeeTableRecords($this->documents)
        ->assertCanNotSeeTableRecords($documentsUploadedByAnotherConsultant);
});

describe('share document action', function (): void {
    it('should be able to share documents', function (): void {
        $employee = User::factory()->create();
        Appointment::factory()
            ->for($employee, 'user')
            ->for($this->consultant, 'consultant')
            ->create();

        livewire(ListDocuments::class)
            ->assertOk()
            ->callAction(
                TestAction::make(ShareDocumentFilamentAction::getDefaultName())->table($this->documents->first()),
                data: [
                    'employee_id' => $employee->getKey(),
                ])
            ->assertHasNoFormErrors();
        assertDatabaseHas(DocumentShare::class, [
            'consultant_id' => $this->consultant->getKey(),
            'document_id' => $this->documents->first()->getKey(),
            'employee_id' => $employee->getKey(),
        ]);
    });

    it("should not share document with employee that is not a consultant's client", function (): void {
        $notClient = User::factory()->create();

        livewire(ListDocuments::class)
            ->assertOk()
            ->callAction(
                TestAction::make(ShareDocumentFilamentAction::getDefaultName())->table($this->documents->first()),
                data: [
                    'employee_id' => $notClient->getKey(),
                ])
            ->assertHasNoFormErrors(['employee_id' => 'The selected cliente is invalid.']);

        assertDatabaseCount(DocumentShare::class, 0);
    });
    it('should not be able to share the same document twice for the same employee', function (): void {
        $employee = User::factory()->create();
        Appointment::factory()
            ->for($employee, 'user')
            ->for($this->consultant, 'consultant')
            ->create();

        DocumentShare::factory()
            ->for($employee, 'employee')
            ->for($this->consultant, 'consultant')
            ->for($this->documents->first(), 'document')
            ->active()
            ->create();

        livewire(ListDocuments::class)
            ->assertOk()
            ->callAction(
                TestAction::make(ShareDocumentFilamentAction::getDefaultName())->table($this->documents->first()),
                data: [
                    'employee_id' => $employee->getKey(),
                ])
            ->assertHasNoFormErrors(['employee_id' => 'The selected cliente is invalid.']);

        assertDatabaseCount(DocumentShare::class, 1);
        assertDatabaseHas(DocumentShare::class, [
            'consultant_id' => $this->consultant->getKey(),
            'document_id' => $this->documents->first()->getKey(),
            'employee_id' => $employee->getKey(),
        ]);
    });
});
