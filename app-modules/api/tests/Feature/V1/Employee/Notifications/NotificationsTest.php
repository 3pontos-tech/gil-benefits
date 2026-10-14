<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Notifications\Notification as FilamentNotification;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentCancelledNotification;
use TresPontosTech\Appointments\Notifications\AppointmentCompletedNotification;
use TresPontosTech\Credits\Notifications\CreditsDeliveredNotification;

use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;

beforeEach(function (): void {
    $this->travelTo('2026-10-08 10:00:00');
    $this->employee = actingAsApiEmployee();
    $this->appointment = Appointment::factory()->create(['user_id' => $this->employee->id]);
});

it('lists the employee notices the app shows, newest first, with the deep-link ids', function (): void {
    $this->employee->notify(new CreditsDeliveredNotification(3));
    $this->travel(1)->hour();
    $this->employee->notify(new AppointmentCancelledNotification($this->appointment, isLate: false));

    getJson(route('api.v1.notifications.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.type', 'appointment_cancelled')
        ->assertJsonPath('data.0.data.appointment_id', $this->appointment->id)
        ->assertJsonPath('data.0.data.status', 'warning')
        ->assertJsonPath('data.0.read_at', null)
        ->assertJsonPath('data.1.type', 'credits_delivered')
        ->assertJsonMissingPath('data.1.data.appointment_id');
});

it('leaves out notices the app does not show', function (): void {
    FilamentNotification::make()->title('Pagamento recebido')->sendToDatabase($this->employee);
    $this->employee->notify(new AppointmentCompletedNotification($this->appointment));

    getJson(route('api.v1.notifications.index'))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'appointment_completed')
        ->assertJsonPath('meta.unread', 1);
});

it('only lists the employee own notices', function (): void {
    User::factory()->create()->notify(new AppointmentCompletedNotification($this->appointment));

    getJson(route('api.v1.notifications.index'))
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.unread', 0);
});

it('counts the unread next to the pagination meta and lowers it on read', function (): void {
    $this->employee->notify(new AppointmentCompletedNotification($this->appointment));
    $this->employee->notify(new CreditsDeliveredNotification(1));

    $first = $this->employee->notifications()->latest()->firstOrFail();

    getJson(route('api.v1.notifications.index'))
        ->assertJsonPath('meta.unread', 2)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.per_page', 20);

    patchJson(route('api.v1.notifications.read', $first->id))
        ->assertOk()
        ->assertJsonPath('data.id', $first->id)
        ->assertJsonPath('data.read_at', '2026-10-08T10:00:00-03:00');

    $this->travel(1)->day();

    patchJson(route('api.v1.notifications.read', $first->id))
        ->assertJsonPath('data.read_at', '2026-10-08T10:00:00-03:00');

    getJson(route('api.v1.notifications.index'))->assertJsonPath('meta.unread', 1);
});

it('answers 404 when marking a notice of someone else', function (): void {
    $other = User::factory()->create();
    $other->notify(new AppointmentCompletedNotification($this->appointment));

    patchJson(route('api.v1.notifications.read', $other->notifications()->value('id')))->assertNotFound();
});

it('exposes every key the app reads from Notification', function (): void {
    $this->employee->notify(new AppointmentCancelledNotification($this->appointment, isLate: true));

    $response = getJson(route('api.v1.notifications.index'))
        ->assertJsonStructure([
            'data' => [['id', 'type', 'data' => ['title', 'body', 'status', 'icon', 'format', 'appointment_id'], 'read_at', 'created_at']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total', 'unread'],
        ])
        ->assertJsonPath('data.0.type', 'appointment_cancelled_late')
        ->assertJsonPath('data.0.data.format', 'filament');

    expect($response->json('data.0.created_at'))->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});
