<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\Notifications\ContentNotification;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TresPontosTech\Api\Models\PushToken;
use TresPontosTech\Appointments\Enums\AppointmentChange;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentCancelledNotification;
use TresPontosTech\Appointments\Notifications\AppointmentChangedNotification;
use TresPontosTech\Appointments\Notifications\AppointmentConfirmedNotification;
use TresPontosTech\Appointments\Notifications\AppointmentReminderNotification;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Notifications\MaterialSharedNotification;
use TresPontosTech\Credits\Notifications\CreditsExpiringNotification;

/**
 * O push é contrato com o app publicado (gil-benefits-mobile#9): o app decide a tela pelo `type`,
 * abre pelos ids e escolhe o canal Android pelo `channelId`, que não muda depois de criado. Mudar
 * uma chave daqui é MAJOR (RELEASING.md); acrescentar é MINOR.
 */
beforeEach(function (): void {
    $this->travelTo('2026-09-08 09:00:00');
    $this->employee = standaloneSubscriber();
    PushToken::factory()->create(['user_id' => $this->employee->getKey()]);
    Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket-1']]])]);
});

/**
 * @return array<string, mixed>
 */
function sentPushMessage(User $employee, ContentNotification $notification): array
{
    $employee->notify($notification);

    $messages = [];
    Http::assertSent(function (Request $request) use (&$messages): bool {
        $messages = $request->data();

        return true;
    });

    return $messages[0];
}

/**
 * Só o material não tem validade: ele serve enquanto estiver na lista.
 */
it('sends each push type with its contract', function (Closure $notification, string $type, string $channel, array $dataKeys, bool $urgent): void {
    $message = sentPushMessage($this->employee, $notification());

    $keys = ['to', 'title', 'body', 'data', 'sound', 'priority', 'channelId', ...($type === 'material_shared' ? [] : ['expiration'])];

    expect(array_keys($message))->toEqualCanonicalizing($keys)
        ->and($message['data']['type'])->toBe($type)
        ->and(array_keys($message['data']))->toEqualCanonicalizing($dataKeys)
        ->and($message['channelId'])->toBe($channel)
        ->and($message['priority'])->toBe($urgent ? 'high' : 'default');
})->with([
    'appointment_confirmed' => [
        fn (): AppointmentConfirmedNotification => new AppointmentConfirmedNotification(Appointment::factory()->withStatus(AppointmentStatus::Active)->create(['appointment_at' => '2026-09-10 10:00:00'])),
        'appointment_confirmed', 'appointments', ['type', 'notification_id', 'appointment_id'], true,
    ],
    'appointment_changed' => [
        fn (): AppointmentChangedNotification => new AppointmentChangedNotification(Appointment::factory()->withStatus(AppointmentStatus::Active)->create(['appointment_at' => '2026-09-10 10:00:00']), AppointmentChange::Time),
        'appointment_changed', 'appointments', ['type', 'notification_id', 'appointment_id', 'change'], true,
    ],
    'appointment_cancelled by the team' => [
        fn (): AppointmentCancelledNotification => new AppointmentCancelledNotification(Appointment::factory()->create(['appointment_at' => '2026-09-10 10:00:00']), isLate: false, cancelledBy: CancellationActor::Admin),
        'appointment_cancelled', 'appointments', ['type', 'notification_id', 'appointment_id'], true,
    ],
    'appointment_reminder' => [
        fn (): AppointmentReminderNotification => new AppointmentReminderNotification(Appointment::factory()->withStatus(AppointmentStatus::Active)->create(['appointment_at' => '2026-09-08 09:50:00'])),
        'appointment_reminder', 'appointments', ['type', 'appointment_id'], true,
    ],
    'material_shared' => [
        fn (): MaterialSharedNotification => new MaterialSharedNotification(Document::factory()->create()),
        'material_shared', 'general', ['type', 'notification_id', 'document_id'], false,
    ],
    'credits_expiring' => [
        fn (): CreditsExpiringNotification => new CreditsExpiringNotification(now()->addDays(7)->endOfDay()),
        'credits_expiring', 'general', ['type', 'notification_id'], false,
    ],
]);
