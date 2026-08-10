<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\AppointmentFeedbackResource;
use TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\Pages\ListAppointmentFeedbacks;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('renders the evaluations list', function (): void {
    livewire(ListAppointmentFeedbacks::class)
        ->assertOk();
});

it('lists all evaluations', function (): void {
    $feedbacks = AppointmentFeedback::factory()->count(5)->create(['comment' => 'Ótimo atendimento']);

    livewire(ListAppointmentFeedbacks::class)
        ->assertOk()
        ->assertCanSeeTableRecords($feedbacks);
});

it('sorts evaluations by newest first by default', function (): void {
    $older = AppointmentFeedback::factory()->create([
        'comment' => 'Antiga',
        'created_at' => now()->subDays(2),
    ]);
    $newer = AppointmentFeedback::factory()->create([
        'comment' => 'Recente',
        'created_at' => now(),
    ]);

    livewire(ListAppointmentFeedbacks::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

it('hides evaluations of soft-deleted appointments', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create();
    $orphaned = AppointmentFeedback::factory()->create([
        'appointment_id' => $appointment->id,
        'comment' => 'Consulta apagada',
    ]);
    $visible = AppointmentFeedback::factory()->create(['comment' => 'Consulta ativa']);

    $appointment->delete();

    livewire(ListAppointmentFeedbacks::class)
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$orphaned]);
});

it('does not expose create, edit or delete', function (): void {
    expect(AppointmentFeedbackResource::canCreate())->toBeFalse();

    $feedback = AppointmentFeedback::factory()->create(['comment' => 'Ótimo atendimento']);

    expect(AppointmentFeedbackResource::canEdit($feedback))->toBeFalse()
        ->and(AppointmentFeedbackResource::canDelete($feedback))->toBeFalse()
        ->and(AppointmentFeedbackResource::canDeleteAny())->toBeFalse();
});

it('forbids users without permission', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Roles::User->value);

    filament()->setCurrentPanel(FilamentPanel::Admin->value);
    actingAs($user);

    livewire(ListAppointmentFeedbacks::class)
        ->assertForbidden();
});

it('has the expected columns with secondary ones hidden by default', function (): void {
    AppointmentFeedback::factory()->create(['comment' => 'Ótimo atendimento']);

    livewire(ListAppointmentFeedbacks::class)
        ->assertTableColumnExists('created_at')
        ->assertTableColumnExists('rating')
        ->assertTableColumnExists('comment')
        ->assertTableColumnExists('user.name')
        ->assertTableColumnExists('appointment.consultant.name')
        ->assertTableColumnExists('appointment.company.name')
        ->assertTableColumnExists('appointment.appointment_at')
        ->assertTableColumnExists('appointment.status')
        ->assertTableColumnExists('updated_at')
        ->assertCanRenderTableColumn('created_at')
        ->assertCanRenderTableColumn('rating')
        ->assertCanNotRenderTableColumn('appointment.status')
        ->assertCanNotRenderTableColumn('updated_at');
});
