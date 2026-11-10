<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\Notifications\ContentNotification;
use App\Notifications\NotificationContent;
use App\Notifications\NotificationKind;
use App\Notifications\NotificationPush;
use App\Notifications\NotificationTone;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use TresPontosTech\Api\Models\PushTicket;
use TresPontosTech\Api\Models\PushToken;

/**
 * Um aviso qualquer com texto de push; `$pushOnly` tira a linha do sino, como o lembrete.
 */
function pushNotice(?NotificationPush $push, bool $pushOnly = false): ContentNotification
{
    return new class($push, $pushOnly) extends ContentNotification
    {
        public function __construct(private readonly ?NotificationPush $push, private readonly bool $pushOnly) {}

        public function via(object $notifiable): array
        {
            return $this->pushOnly ? $this->withPush($notifiable, []) : parent::via($notifiable);
        }

        public function content(): NotificationContent
        {
            return new NotificationContent(
                kind: NotificationKind::AppointmentConfirmed,
                title: 'Agendamento confirmado',
                body: 'Painel',
                tone: NotificationTone::Success,
                appointmentId: 'a-1',
                push: $this->push,
            );
        }
    };
}

/**
 * @param  list<array<string, mixed>>  $tickets
 */
function expoAnswers(array $tickets): void
{
    Http::fake(['exp.host/--/api/v2/push/send' => Http::response(['data' => $tickets])]);
}

beforeEach(function (): void {
    $this->employee = standaloneSubscriber();
});

it('sends the push copy, the type and the ids to every phone of the person', function (): void {
    $phones = PushToken::factory()->count(2)->create(['user_id' => $this->employee->getKey()]);
    expoAnswers([['status' => 'ok', 'id' => 'ticket-1'], ['status' => 'ok', 'id' => 'ticket-2']]);

    $this->employee->notify(pushNotice(new NotificationPush('Encontro confirmado', 'Seu encontro de qui., 10/09, às 10:00 está confirmado.', urgent: true)));

    $stored = $this->employee->notifications()->sole();

    Http::assertSent(function (Request $request) use ($phones, $stored): bool {
        $messages = $request->data();

        return collect($messages)->pluck('to')->sort()->values()->all() === $phones->pluck('token')->sort()->values()->all()
            && $messages[0]['title'] === 'Encontro confirmado'
            && $messages[0]['priority'] === 'high'
            && $messages[0]['data'] === ['type' => 'appointment_confirmed', 'notification_id' => $stored->getKey(), 'appointment_id' => 'a-1'];
    });

    expect(PushTicket::query()->pluck('id')->sort()->values()->all())->toBe(['ticket-1', 'ticket-2']);
});

it('leaves the notification id out when the notice is push only', function (): void {
    PushToken::factory()->create(['user_id' => $this->employee->getKey()]);
    expoAnswers([['status' => 'ok', 'id' => 'ticket-1']]);

    $this->employee->notify(pushNotice(new NotificationPush('Seu encontro começa em 1 hora', 'Hoje, às 10:00.'), pushOnly: true));

    expect($this->employee->notifications()->count())->toBe(0);
    Http::assertSent(fn (Request $request): bool => $request->data()[0]['data'] === ['type' => 'appointment_confirmed', 'appointment_id' => 'a-1']
        && $request->data()[0]['priority'] === 'default');
});

it('sends no push for a notice without push copy, and keeps the bell row', function (): void {
    PushToken::factory()->create(['user_id' => $this->employee->getKey()]);
    Http::fake();

    $this->employee->notify(pushNotice(null));

    Http::assertNothingSent();
    expect($this->employee->notifications()->count())->toBe(1);
});

it('sends nothing to someone without a registered phone', function (): void {
    Http::fake();

    $this->employee->notify(pushNotice(new NotificationPush('Encontro confirmado', 'Corpo')));

    Http::assertNothingSent();
});

it('sends nothing to someone who no longer has the app', function (): void {
    $former = User::factory()->create();
    PushToken::factory()->create(['user_id' => $former->getKey()]);
    Http::fake();

    $former->notify(pushNotice(new NotificationPush('Encontro confirmado', 'Corpo')));

    Http::assertNothingSent();
});

it('forgets a phone the Expo says no longer exists', function (): void {
    $phone = PushToken::factory()->create(['user_id' => $this->employee->getKey()]);
    expoAnswers([['status' => 'error', 'message' => '"' . $phone->token . '" is not a registered push notification recipient', 'details' => ['error' => 'DeviceNotRegistered']]]);

    $this->employee->notify(pushNotice(new NotificationPush('Encontro confirmado', 'Corpo')));

    expect(PushToken::query()->count())->toBe(0)
        ->and(PushTicket::query()->count())->toBe(0);
});

it('sends the access token when the project requires it', function (): void {
    config(['api.push.access_token' => 'expo-secret']);
    PushToken::factory()->create(['user_id' => $this->employee->getKey()]);
    expoAnswers([['status' => 'ok', 'id' => 'ticket-1']]);

    $this->employee->notify(pushNotice(new NotificationPush('Encontro confirmado', 'Corpo')));

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer expo-secret'));
});

it('tries again when the Expo is busy', function (): void {
    Sleep::fake();
    PushToken::factory()->create(['user_id' => $this->employee->getKey()]);
    Http::fakeSequence('exp.host/*')
        ->push(['errors' => [['code' => 'TOO_MANY_REQUESTS']]], 429)
        ->push(['data' => [['status' => 'ok', 'id' => 'ticket-1']]]);

    $this->employee->notify(pushNotice(new NotificationPush('Encontro confirmado', 'Corpo')));

    expect(PushTicket::query()->pluck('id')->all())->toBe(['ticket-1']);
});

it('drops the push when the moment has passed', function (): void {
    PushToken::factory()->create(['user_id' => $this->employee->getKey()]);
    expoAnswers([['status' => 'ok', 'id' => 'ticket-1']]);
    $startsAt = now()->addHour();

    $this->employee->notify(pushNotice(new NotificationPush('Seu encontro começa em 1 hora', 'Hoje.', expiresAt: $startsAt)));

    Http::assertSent(fn (Request $request): bool => $request->data()[0]['expiration'] === $startsAt->getTimestamp());
});
