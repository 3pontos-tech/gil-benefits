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

it('renderiza a página de visualização do agendamento', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertOk()
        ->assertSee($appointment->user->name)
        ->assertSee($appointment->status->getLabel());
});

it('exibe nome do usuário e do consultor na seção de participantes', function (): void {
    $appointment = Appointment::factory()->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee($appointment->user->name)
        ->assertSee($appointment->consultant->name);
});

it('oculta seção do consultor quando agendamento não tem consultor', function (): void {
    $appointment = Appointment::factory()->withoutConsultant()->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee($appointment->user->name)
        ->assertDontSee(__('appointments::resources.appointments.table.columns.consultant'));
});

it('exibe badge de categoria quando presente', function (): void {
    $appointment = Appointment::factory()->create([
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
    ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(AppointmentCategoryEnum::PersonalFinance->getLabel());
});

it('exibe badge de status do agendamento', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()->withStatus($status)->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee($status->getLabel());
})->with([
    'rascunho' => AppointmentStatus::Draft,
    'pendente' => AppointmentStatus::Pending,
    'ativo' => AppointmentStatus::Active,
    'concluído' => AppointmentStatus::Completed,
    'cancelado' => AppointmentStatus::Cancelled,
]);

it('exibe data e hora do agendamento na sidebar', function (): void {
    $appointment = Appointment::factory()->create([
        'appointment_at' => '2026-03-20 14:30:00',
    ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee('20/03/2026')
        ->assertSee('14:30');
});

it('exibe link da reunião quando disponível', function (): void {
    $appointment = Appointment::factory()->create([
        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
    ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(__('appointments::resources.appointments.form.meeting_url'))
        ->assertSeeHtml('href="https://meet.google.com/abc-defg-hij"');
});

it('exibe observações quando presentes', function (): void {
    $appointment = Appointment::factory()->create([
        'notes' => 'Cliente solicitou foco em investimentos de longo prazo.',
    ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee('Cliente solicitou foco em investimentos de longo prazo.');
});

it('oculta seção de observações quando vazia', function (): void {
    $appointment = Appointment::factory()->create(['notes' => null]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertDontSee(__('appointments::resources.appointments.wizard.labels.notes'));
});

it('exibe ata com conteúdo renderizado em markdown', function (): void {
    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()
        ->recycle($appointment)
        ->create(['content' => '## Resumo da Reunião' . "\n\nCliente demonstrou interesse."]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(__('appointments::resources.appointments.infolist.ai.content'))
        ->assertSeeHtml('Resumo da Reunião')
        ->assertSee('Cliente demonstrou interesse.');
});

it('exibe badge de rascunho quando a ata não foi publicada', function (): void {
    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()
        ->recycle($appointment)
        ->create(['content' => 'Conteúdo da ata', 'published_at' => null]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(__('appointments::resources.appointments.infolist.ai.draft'));
});

it('exibe badge com data de publicação quando a ata foi publicada', function (): void {
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

it('exibe indicador de processamento quando record existe mas content é null', function (): void {
    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()
        ->recycle($appointment)
        ->draft()
        ->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(__('panel-admin::resources.appointments.view.processing'));
});

it('exibe resumo interno na tab quando disponível', function (): void {
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

it('não exibe tab de resumo interno quando ausente', function (): void {
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

it('exibe metadados da IA quando disponíveis', function (): void {
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

it('exibe documentos do colaborador quando existem', function (): void {
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

it('exibe placeholder quando colaborador não tem documentos', function (): void {
    $appointment = Appointment::factory()->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee(__('appointments::resources.appointments.infolist.documents.empty'));
});

it('oculta seção de IA quando não há record', function (): void {
    $appointment = Appointment::factory()->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertDontSee(__('appointments::resources.appointments.infolist.ai_generation'))
        ->assertDontSee(__('panel-admin::resources.appointments.view.processing'));
});

it('exibe timestamps de criação e atualização nos metadados', function (): void {
    $appointment = Appointment::factory()->create([
        'created_at' => '2026-03-15 10:00:00',
        'updated_at' => '2026-03-15 10:00:00',
    ]);

    livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
        ->assertSee('15/03/2026 10:00');
});
