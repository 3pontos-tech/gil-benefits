<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use TresPontosTech\Support\Actions\CreateSupportTicketAction;
use TresPontosTech\Support\Actions\ResendTicketNotificationAction;
use TresPontosTech\Support\Actions\TransitionSupportTicketStatusAction;
use TresPontosTech\Support\Contracts\TicketNotificationChannel;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\DTOs\TicketNotificationResult;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketNotificationChannelEnum;
use TresPontosTech\Support\Enums\TicketNotificationEventEnum;
use TresPontosTech\Support\Enums\TicketNotificationStatusEnum;
use TresPontosTech\Support\Exceptions\TicketNotificationFailedException;
use TresPontosTech\Support\Jobs\SendTicketNotificationJob;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\SupportTicketNotification;
use TresPontosTech\Support\TicketNotificationManager;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\freezeTime;

final class FakeTicketNotificationChannel implements TicketNotificationChannel
{
    public int $sends = 0;

    public function __construct(
        public ?string $text = 'Your ticket changed.',
        public TicketNotificationResult|Throwable|null $result = null,
    ) {}

    public function message(SupportTicket $ticket, ?SupportTicketStatusEnum $from, SupportTicketStatusEnum $to): ?string
    {
        return $this->text;
    }

    public function send(SupportTicketNotification $notification): TicketNotificationResult
    {
        ++$this->sends;

        if ($this->result instanceof Throwable) {
            throw $this->result;
        }

        return $this->result ?? TicketNotificationResult::sent(200);
    }
}

function fakeNotificationChannel(FakeTicketNotificationChannel $channel = new FakeTicketNotificationChannel): FakeTicketNotificationChannel
{
    resolve(TicketNotificationManager::class)
        ->forgetDrivers()
        ->extend(TicketNotificationChannelEnum::Whatsapp->value, fn (): TicketNotificationChannel => $channel);

    return $channel;
}

function openSupportTicket(): SupportTicket
{
    Mail::fake();

    return resolve(CreateSupportTicketAction::class)->execute(new CreateSupportTicketDTO(
        category: SupportTicketCategoryEnum::Bug,
        subject: 'Subject',
        description: 'Description',
        userId: User::factory()->create()->id,
        environment: 'testing',
    ));
}

function runNotificationJob(SupportTicketNotification $notification): void
{
    (new SendTicketNotificationJob($notification->id))->handle(resolve(TicketNotificationManager::class));
}

describe('queueing', function (): void {
    it('queues a notification on creation for each channel that has a message', function (): void {
        Queue::fake([SendTicketNotificationJob::class]);
        fakeNotificationChannel(new FakeTicketNotificationChannel('Ticket created.'));

        $ticket = openSupportTicket();

        $notification = SupportTicketNotification::query()->sole();

        expect($notification->support_ticket_id)->toBe($ticket->id)
            ->and($notification->channel)->toBe(TicketNotificationChannelEnum::Whatsapp)
            ->and($notification->event)->toBe(TicketNotificationEventEnum::Created)
            ->and($notification->from_status)->toBeNull()
            ->and($notification->to_status)->toBe(SupportTicketStatusEnum::Pending)
            ->and($notification->message)->toBe('Ticket created.')
            ->and($notification->status)->toBe(TicketNotificationStatusEnum::Pending);

        Queue::assertPushed(SendTicketNotificationJob::class, fn (SendTicketNotificationJob $job): bool => $job->notificationId === $notification->id);
    });

    it('queues a notification on a status change with the transition', function (): void {
        Queue::fake([SendTicketNotificationJob::class]);
        Mail::fake();
        fakeNotificationChannel();

        $ticket = SupportTicket::factory()->create(['status' => SupportTicketStatusEnum::Pending]);

        resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::InProgress);

        $notification = SupportTicketNotification::query()->sole();

        expect($notification->event)->toBe(TicketNotificationEventEnum::StatusChanged)
            ->and($notification->from_status)->toBe(SupportTicketStatusEnum::Pending)
            ->and($notification->to_status)->toBe(SupportTicketStatusEnum::InProgress);

        Queue::assertPushed(SendTicketNotificationJob::class, 1);
    });

    it('queues nothing when the channel has no message for the ticket', function (): void {
        Queue::fake([SendTicketNotificationJob::class]);
        fakeNotificationChannel(new FakeTicketNotificationChannel(text: null));

        openSupportTicket();

        assertDatabaseCount(SupportTicketNotification::class, 0);
        Queue::assertNotPushed(SendTicketNotificationJob::class);
    });
});

describe('sending', function (): void {
    it('marks the notification sent with the response code', function (): void {
        freezeTime();
        $channel = fakeNotificationChannel(new FakeTicketNotificationChannel(result: TicketNotificationResult::sent(201)));
        $notification = SupportTicketNotification::factory()->create();

        runNotificationJob($notification);

        $notification->refresh();

        expect($channel->sends)->toBe(1)
            ->and($notification->status)->toBe(TicketNotificationStatusEnum::Sent)
            ->and($notification->attempts)->toBe(1)
            ->and($notification->response_code)->toBe(201)
            ->and($notification->sent_at?->toDateTimeString())->toBe(now()->toDateTimeString());
    });

    it('records a rejected attempt and throws so the queue retries', function (): void {
        fakeNotificationChannel(new FakeTicketNotificationChannel(result: TicketNotificationResult::failed(401, '{"message":"Unauthenticated."}')));
        $notification = SupportTicketNotification::factory()->create();

        expect(fn () => runNotificationJob($notification))->toThrow(TicketNotificationFailedException::class);

        $notification->refresh();

        expect($notification->status)->toBe(TicketNotificationStatusEnum::Pending)
            ->and($notification->attempts)->toBe(1)
            ->and($notification->response_code)->toBe(401)
            ->and($notification->error)->toBe('{"message":"Unauthenticated."}');
    });

    it('records an exception thrown by the channel as a failed attempt', function (): void {
        fakeNotificationChannel(new FakeTicketNotificationChannel(result: new RuntimeException('boom')));
        $notification = SupportTicketNotification::factory()->create();

        expect(fn () => runNotificationJob($notification))->toThrow(TicketNotificationFailedException::class);

        expect($notification->refresh()->error)->toBe('boom')
            ->and($notification->attempts)->toBe(1);
    });

    it('marks the notification skipped without counting an attempt', function (): void {
        fakeNotificationChannel(new FakeTicketNotificationChannel(result: TicketNotificationResult::skipped('not configured')));
        $notification = SupportTicketNotification::factory()->create();

        runNotificationJob($notification);

        $notification->refresh();

        expect($notification->status)->toBe(TicketNotificationStatusEnum::Skipped)
            ->and($notification->error)->toBe('not configured')
            ->and($notification->attempts)->toBe(0);
    });

    it('does not send a notification that left the pending state', function (TicketNotificationStatusEnum $status): void {
        $channel = fakeNotificationChannel();
        $notification = SupportTicketNotification::factory()->create(['status' => $status]);

        runNotificationJob($notification);

        expect($channel->sends)->toBe(0);
    })->with([
        'sent' => TicketNotificationStatusEnum::Sent,
        'failed' => TicketNotificationStatusEnum::Failed,
        'skipped' => TicketNotificationStatusEnum::Skipped,
    ]);

    it('marks the notification failed once the retries run out', function (): void {
        $notification = SupportTicketNotification::factory()->create();

        (new SendTicketNotificationJob($notification->id))->failed(new RuntimeException('boom'));

        expect($notification->refresh()->status)->toBe(TicketNotificationStatusEnum::Failed);
    });
});

describe('resending', function (): void {
    it('requeues a failed notification, keeping the same row', function (): void {
        Queue::fake([SendTicketNotificationJob::class]);
        $notification = SupportTicketNotification::factory()->failed()->create();

        resolve(ResendTicketNotificationAction::class)->execute($notification);

        expect($notification->refresh()->status)->toBe(TicketNotificationStatusEnum::Pending);
        assertDatabaseCount(SupportTicketNotification::class, 1);
        Queue::assertPushed(SendTicketNotificationJob::class, fn (SendTicketNotificationJob $job): bool => $job->notificationId === $notification->id);
    });

    it('does not resend a notification that did not fail', function (): void {
        Queue::fake([SendTicketNotificationJob::class]);
        $notification = SupportTicketNotification::factory()->sent()->create();

        resolve(ResendTicketNotificationAction::class)->execute($notification);

        expect($notification->refresh()->status)->toBe(TicketNotificationStatusEnum::Sent);
        Queue::assertNotPushed(SendTicketNotificationJob::class);
    });
});
