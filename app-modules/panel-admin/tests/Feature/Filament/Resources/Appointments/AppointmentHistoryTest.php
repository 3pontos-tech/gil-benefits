<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentHistory;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\EditAppointment;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\ViewAppointment;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\RelationManagers\AppointmentHistoryRelationManager;
use Zap\Facades\Zap;

use function Livewire\invade;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    LaravelNotification::fake();
    Bus::fake();
    Mail::fake();

    $this->makeAvailable = function (CarbonInterface $date, Consultant $consultant): void {
        Zap::for($consultant)
            ->named('Availability')
            ->availability()
            ->from($date->toDateString())
            ->to($date->copy()->addDay()->toDateString())
            ->addPeriod('08:00', '18:00')
            ->save();
    };

    // Google Calendar is not the subject here: accept any call so the flow can complete.
    $this->fakeCalendar = function (): void {
        $client = Mockery::mock(GoogleCalendarClient::class);
        $client->shouldReceive('getAccessToken')->andReturn('tok');
        $client->shouldReceive('deleteEvent')->andReturnNull();
        $client->shouldReceive('patchEvent')->andReturnNull();
        $client->shouldReceive('createEvent')->andReturn(['id' => 'evt', 'hangoutLink' => null]);
        app()->instance(GoogleCalendarClient::class, $client);
    };
});

it('records a re_scheduled history entry when only the time changes', function (): void {
    $day = Date::now()->addDays(3);
    $from = $day->copy()->setTime(10, 0);
    $to = $day->copy()->setTime(15, 0);

    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    ($this->makeAvailable)($day, $consultant);
    ($this->fakeCalendar)();

    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'appointment_at' => $from,
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'evt-keep',
    ]);
    resolve(AssignConsultantAction::class)->handle($appointment);

    livewire(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->fillForm(['appointment_at' => $to->toDateTimeString()])
        ->fillForm(['consultant_id' => $consultant->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $histories = AppointmentHistory::query()->where('appointment_id', $appointment->id)->get();

    expect($histories)->toHaveCount(1);

    $history = $histories->first();

    expect($history->action_type)->toBe(AppointmentHistoryActionType::ReScheduled)
        ->and($history->actor_id)->not->toBeNull()
        ->and($history->actor_type)->toBe(AppointmentHistoryActor::Admin)
        ->and(Date::parse($history->old_values['appointment_at'])->format('H:i'))->toBe('10:00')
        ->and(Date::parse($history->new_values['appointment_at'])->format('H:i'))->toBe('15:00');
});

it('records a consultant_changed history entry when the consultant is swapped', function (): void {
    $day = Date::now()->addDays(3);
    $at = $day->copy()->setTime(10, 0);

    $previous = Consultant::factory()->create(['email' => 'previous@workspace.com']);
    $next = Consultant::factory()->create(['email' => 'next@workspace.com']);
    ($this->makeAvailable)($day, $previous);
    ($this->makeAvailable)($day, $next);
    ($this->fakeCalendar)();

    $appointment = Appointment::factory()->create([
        'consultant_id' => $previous->id,
        'appointment_at' => $at,
        'status' => AppointmentStatus::Active,
    ]);
    resolve(AssignConsultantAction::class)->handle($appointment);

    livewire(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->fillForm(['consultant_id' => $next->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $history = AppointmentHistory::query()
        ->where('appointment_id', $appointment->id)
        ->where('action_type', AppointmentHistoryActionType::ConsultantChanged)
        ->firstOrFail();

    expect($history->old_values['consultant_id'])->toBe($previous->id)
        ->and($history->new_values['consultant_id'])->toBe($next->id);
});

it('records a consultant_assigned history entry when a consultant is set on an appointment without one', function (): void {
    $day = Date::now()->addDays(3);
    $at = $day->copy()->setTime(10, 0);

    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    ($this->makeAvailable)($day, $consultant);
    ($this->fakeCalendar)();

    $appointment = Appointment::factory()->create([
        'consultant_id' => null,
        'appointment_at' => $at,
        'status' => AppointmentStatus::Pending,
    ]);

    livewire(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->fillForm(['consultant_id' => $consultant->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $history = AppointmentHistory::query()
        ->where('appointment_id', $appointment->id)
        ->where('action_type', AppointmentHistoryActionType::ConsultantAssigned)
        ->firstOrFail();

    expect($history->new_values['consultant_id'])->toBe($consultant->id)
        ->and(AppointmentHistory::query()
            ->where('appointment_id', $appointment->id)
            ->where('action_type', AppointmentHistoryActionType::ConsultantChanged)
            ->exists()
        )->toBeFalse();
});

it('shows the appointment histories in the relation manager, newest first', function (): void {
    $appointment = Appointment::factory()->create();

    $older = AppointmentHistory::factory()
        ->actionType(AppointmentHistoryActionType::ConsultantAssigned)
        ->create(['appointment_id' => $appointment->id, 'created_at' => Date::now()->subDay()]);

    $newer = AppointmentHistory::factory()
        ->actionType(AppointmentHistoryActionType::ReScheduled)
        ->create(['appointment_id' => $appointment->id, 'created_at' => Date::now()]);

    $otherAppointmentHistory = AppointmentHistory::factory()->create();

    livewire(AppointmentHistoryRelationManager::class, [
        'ownerRecord' => $appointment,
        'pageClass' => ViewAppointment::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$newer, $older])
        ->assertCanNotSeeTableRecords([$otherAppointmentHistory])
        ->assertSeeText(AppointmentHistoryActionType::ReScheduled->getLabel());
});

it('resolves consultant names in the history detail modal', function (): void {
    $appointment = Appointment::factory()->create();

    $previous = Consultant::factory()->create(['name' => 'Ana Prévia']);
    $current = Consultant::factory()->create(['name' => 'Bruno Atual']);

    $history = AppointmentHistory::factory()
        ->actionType(AppointmentHistoryActionType::ConsultantChanged)
        ->create([
            'appointment_id' => $appointment->id,
            'old_values' => ['consultant_id' => $previous->id],
            'new_values' => ['consultant_id' => $current->id],
        ]);

    livewire(AppointmentHistoryRelationManager::class, [
        'ownerRecord' => $appointment,
        'pageClass' => ViewAppointment::class,
    ])
        ->mountTableAction('view', $history)
        ->assertSee('Ana Prévia')
        ->assertSee('Bruno Atual')
        ->assertSee(AppointmentHistoryActionType::ConsultantChanged->getLabel());
});

it('renders the no_show_marked history entry in the relation manager table', function (): void {
    $appointment = Appointment::factory()->create();

    $history = AppointmentHistory::factory()
        ->actionType(AppointmentHistoryActionType::NoShowMarked)
        ->create([
            'appointment_id' => $appointment->id,
            'actor_type' => AppointmentHistoryActor::Consultant,
            'old_values' => ['status' => AppointmentStatus::Active->value],
            'new_values' => [
                'status' => AppointmentStatus::NoShow->value,
                'credit_impact' => 'consumed',
            ],
        ]);

    livewire(AppointmentHistoryRelationManager::class, [
        'ownerRecord' => $appointment,
        'pageClass' => ViewAppointment::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$history])
        ->assertSeeText(AppointmentHistoryActionType::NoShowMarked->getLabel())
        ->assertSeeText(AppointmentStatus::NoShow->getLabel());
});

/**
 * Filament renders a mounted action's modal body through a Livewire
 * partial-render effect rather than the component's main HTML, so
 * assertSee() after mountTableAction() cannot see it here (its own compiled
 * view also references $this in a way that needs the real Livewire request
 * lifecycle to resolve). Mounting the action still proves the ViewAction
 * wires up against a NoShowMarked record without error; inspecting
 * buildChangeRows() directly exercises the exact
 * creditImpactLabel()/translation path the modal's view renders from.
 */
it('shows the credit impact in the no_show_marked history detail modal', function (): void {
    $appointment = Appointment::factory()->create();

    $history = AppointmentHistory::factory()
        ->actionType(AppointmentHistoryActionType::NoShowMarked)
        ->create([
            'appointment_id' => $appointment->id,
            'actor_type' => AppointmentHistoryActor::Consultant,
            'old_values' => ['status' => AppointmentStatus::Active->value],
            'new_values' => [
                'status' => AppointmentStatus::NoShow->value,
                'credit_impact' => 'consumed',
            ],
        ]);

    $component = livewire(AppointmentHistoryRelationManager::class, [
        'ownerRecord' => $appointment,
        'pageClass' => ViewAppointment::class,
    ])
        ->mountTableAction('view', $history)
        ->assertHasNoTableActionErrors();

    $changes = invade($component->instance())->buildChangeRows($history);

    expect($changes)->toContain([
        'label' => 'Impacto no crédito',
        'value' => 'Crédito consumido',
    ]);
});
