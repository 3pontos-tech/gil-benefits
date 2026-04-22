<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Admin\Filament\Resources\Appointments\Pages\ViewAppointment;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    Storage::fake('public');
    Storage::disk('public')->buildTemporaryUrlsUsing(
        fn (string $path): string => 'https://example.com/fake/' . $path
    );
    $this->appointment = Appointment::factory()->create();
});

it('renders the view page for an appointment', function (): void {
    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertOk()
        ->assertSee($this->appointment->user->name);
});

it('shows anamnese section when user has anamnese', function (): void {
    UserAnamnese::factory()->for($this->appointment->user)->create([
        'main_motivation' => 'Quero sair das dívidas e construir uma reserva de emergência.',
    ]);

    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertOk()
        ->assertSee('Quero sair das dívidas e construir uma reserva de emergência.');
});

it('hides anamnese section when user has no anamnese', function (): void {
    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertOk()
        ->assertDontSee(__('appointments::resources.appointments.infolist.anamnese'));
});

it('shows employee owned documents', function (): void {
    $document = Document::factory()->state([
        'documentable_id' => $this->appointment->user_id,
        'documentable_type' => $this->appointment->user->getMorphClass(),
    ])->create();

    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertOk()
        ->assertSee($document->title);
});

it('shows active shared documents', function (): void {
    $document = Document::factory()->forConsultant()->create(['title' => 'Relatório de Planejamento Compartilhado']);
    DocumentShare::factory()->active()->create([
        'document_id' => $document->id,
        'employee_id' => $this->appointment->user_id,
    ]);

    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertOk()
        ->assertSee('Relatório de Planejamento Compartilhado');
});

it('does not show inactive shared documents', function (): void {
    $document = Document::factory()->forConsultant()->create(['title' => 'Documento Compartilhado Inativo Xz9q']);
    DocumentShare::factory()->notActive()->create([
        'document_id' => $document->id,
        'employee_id' => $this->appointment->user_id,
    ]);

    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertOk()
        ->assertDontSee('Documento Compartilhado Inativo Xz9q');
});

it('exposes the download action for a document owned by the appointment employee', function (): void {
    $document = Document::factory()->state([
        'documentable_id' => $this->appointment->user_id,
        'documentable_type' => $this->appointment->user->getMorphClass(),
    ])->create();

    $action = TestAction::make('downloadDocument')->arguments(['documentId' => $document->id]);

    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertActionVisible($action)
        ->assertActionShouldOpenUrlInNewTab($action);
});

it('hides the download action for a document owned by another employee', function (): void {
    $otherUser = User::factory()->create();
    $foreignDocument = Document::factory()->state([
        'documentable_id' => $otherUser->id,
        'documentable_type' => $otherUser->getMorphClass(),
    ])->create();

    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertActionHidden(TestAction::make('downloadDocument')->arguments(['documentId' => $foreignDocument->id]));
});

it('exposes the shared download action for an active share', function (): void {
    $document = Document::factory()->forConsultant()->create();
    DocumentShare::factory()->active()->create([
        'document_id' => $document->id,
        'employee_id' => $this->appointment->user_id,
    ]);

    $action = TestAction::make('downloadSharedDocument')->arguments(['documentId' => $document->id]);

    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertActionVisible($action)
        ->assertActionShouldOpenUrlInNewTab($action);
});

it('hides the shared download action when the share is for another employee', function (): void {
    $otherEmployee = User::factory()->create();
    $document = Document::factory()->forConsultant()->create();
    DocumentShare::factory()->active()->create([
        'document_id' => $document->id,
        'employee_id' => $otherEmployee->id,
    ]);

    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertActionHidden(TestAction::make('downloadSharedDocument')->arguments(['documentId' => $document->id]));
});

it('hides the shared download action when the share is inactive', function (): void {
    $document = Document::factory()->forConsultant()->create();
    DocumentShare::factory()->notActive()->create([
        'document_id' => $document->id,
        'employee_id' => $this->appointment->user_id,
    ]);

    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertActionHidden(TestAction::make('downloadSharedDocument')->arguments(['documentId' => $document->id]));
});
