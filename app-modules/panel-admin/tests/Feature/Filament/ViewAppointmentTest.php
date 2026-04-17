<?php

declare(strict_types=1);

use TresPontosTech\Admin\Filament\Resources\Appointments\Pages\ViewAppointment;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
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
