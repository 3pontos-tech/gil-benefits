<?php

declare(strict_types=1);

use TresPontosTech\Admin\Filament\Resources\Appointments\Pages\ViewAppointment;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Consultants\Models\Document;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('renders the appointment view page', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertOk()
        ->assertSee($appointment->user->name)
        ->assertSee($appointment->status->getLabel());
});

it('shows user and consultant names in the participants section', function (): void {
    $appointment = Appointment::factory()->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee($appointment->user->name)
        ->assertSee($appointment->consultant->name);
});

it('hides the consultant section when the appointment has no consultant', function (): void {
    $appointment = Appointment::factory()->withoutConsultant()->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee($appointment->user->name)
        ->assertDontSee(__('appointments::resources.appointments.table.columns.consultant'));
});

it('shows the category badge when present', function (): void {
    $appointment = Appointment::factory()->create([
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
    ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(AppointmentCategoryEnum::PersonalFinance->getLabel());
});

it('shows the appointment status badge', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()->withStatus($status)->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee($status->getLabel());
})->with([
    'draft' => AppointmentStatus::Draft,
    'pending' => AppointmentStatus::Pending,
    'active' => AppointmentStatus::Active,
    'completed' => AppointmentStatus::Completed,
    'cancelled' => AppointmentStatus::Cancelled,
]);

it('shows the appointment date and time in the sidebar', function (): void {
    $appointment = Appointment::factory()->create([
        'appointment_at' => '2026-03-20 14:30:00',
    ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee('20/03/2026')
        ->assertSee('14:30');
});

it('shows the meeting link when available', function (): void {
    $appointment = Appointment::factory()->create([
        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
    ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(__('appointments::resources.appointments.form.meeting_url'))
        ->assertSeeHtml('href="https://meet.google.com/abc-defg-hij"');
});

it('shows notes when present', function (): void {
    $appointment = Appointment::factory()->create([
        'notes' => 'Cliente solicitou foco em investimentos de longo prazo.',
    ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee('Cliente solicitou foco em investimentos de longo prazo.');
});

it('hides the notes section when empty', function (): void {
    $appointment = Appointment::factory()->create(['notes' => null]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertDontSee(__('appointments::resources.appointments.wizard.labels.notes'));
});

it('renders the record content as markdown', function (): void {
    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()
        ->recycle($appointment)
        ->create(['content' => '## Resumo da Reunião' . "\n\nCliente demonstrou interesse."]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(__('appointments::resources.appointments.infolist.ai.content'))
        ->assertSeeHtml('Resumo da Reunião')
        ->assertSee('Cliente demonstrou interesse.');
});

it('shows a draft badge when the record is not published', function (): void {
    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()
        ->recycle($appointment)
        ->create(['content' => 'Conteúdo da ata', 'published_at' => null]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(__('appointments::resources.appointments.infolist.ai.draft'));
});

it('shows a badge with the publication date when the record is published', function (): void {
    $publishedAt = now()->subHour();

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()
        ->recycle($appointment)
        ->create([
            'content' => 'Conteúdo da ata',
            'published_at' => $publishedAt,
        ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee($publishedAt->format('d/m/Y H:i'));
});

it('shows a processing indicator when the record exists but content is null', function (): void {
    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()
        ->recycle($appointment)
        ->draft()
        ->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(__('panel-admin::resources.appointments.view.processing'));
});

it('shows the internal summary tab when available', function (): void {
    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()
        ->recycle($appointment)
        ->create([
            'content' => 'Conteúdo da ata',
            'internal_summary' => '## Resumo para o próximo atendimento' . "\n\nCliente engajado.",
        ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(__('appointments::resources.appointments.infolist.ai.internal_summary'))
        ->assertSee('Cliente engajado.');
});

it('does not show the internal summary tab when absent', function (): void {
    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()
        ->recycle($appointment)
        ->create([
            'content' => 'Conteúdo da ata',
            'internal_summary' => null,
        ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertDontSee(__('appointments::resources.appointments.infolist.ai.internal_summary'));
});

it('shows AI metadata when available', function (): void {
    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()
        ->recycle($appointment)
        ->withTokens(model: 'gemini-2.5-pro', input: 8500, output: 1200)
        ->create(['content' => 'Ata gerada.']);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee('gemini-2.5-pro')
        ->assertSee('8,500')
        ->assertSee('1,200')
        ->assertSee('9,700');
});

it('shows employee documents when present', function (): void {
    $appointment = Appointment::factory()->create();

    Document::factory()
        ->create([
            'title' => 'Extrato bancário março',
            'documentable_id' => $appointment->user_id,
            'documentable_type' => 'users',
        ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee('Extrato bancário março');
});

it('shows a placeholder when the employee has no documents', function (): void {
    $appointment = Appointment::factory()->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(__('appointments::resources.appointments.infolist.documents.empty'));
});

it('hides the AI section when there is no record', function (): void {
    $appointment = Appointment::factory()->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertDontSee(__('appointments::resources.appointments.infolist.ai_generation'))
        ->assertDontSee(__('panel-admin::resources.appointments.view.processing'));
});

it('shows created and updated timestamps in the metadata', function (): void {
    $appointment = Appointment::factory()->create([
        'created_at' => '2026-03-15 10:00:00',
        'updated_at' => '2026-03-15 10:00:00',
    ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee('15/03/2026 10:00');
});
