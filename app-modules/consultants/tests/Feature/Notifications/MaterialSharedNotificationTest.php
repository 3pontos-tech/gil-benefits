<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\Notifications\ContentNotification;
use App\Notifications\NotificationKind;
use Illuminate\Support\Facades\Notification;
use TresPontosTech\Consultants\Actions\NotifySharedDocumentAction;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Notifications\MaterialSharedNotification;

beforeEach(function (): void {
    Notification::fake();
    $this->employee = User::factory()->create();
});

/**
 * @return list<list<string>>
 */
function materialPushChannels(User $employee): array
{
    return Notification::sent($employee, MaterialSharedNotification::class)
        ->map(fn (MaterialSharedNotification $notification): array => $notification->via($employee))
        ->all();
}

it('tells the employee about a new material without its title', function (): void {
    $document = Document::factory()->create(['title' => 'Guia: sair das dívidas em 6 passos']);

    resolve(NotifySharedDocumentAction::class)->handle($this->employee, $document);

    Notification::assertSentTo($this->employee, MaterialSharedNotification::class, function (MaterialSharedNotification $notification) use ($document): bool {
        $content = $notification->content();

        return $content->kind === NotificationKind::MaterialShared
            && $content->documentId === $document->getKey()
            && $content->push?->body === 'Seu consultor compartilhou um material com você. Toque para abrir.'
            && ! str_contains($content->body . $content->push->title, 'dívidas');
    });
});

it('pushes the first material of a burst and keeps every one in the bell', function (): void {
    $documents = Document::factory()->count(3)->create();

    foreach ($documents as $document) {
        resolve(NotifySharedDocumentAction::class)->handle($this->employee, $document);
    }

    expect(materialPushChannels($this->employee))->toBe([
        ['database', ContentNotification::PUSH_CHANNEL],
        ['database'],
        ['database'],
    ]);
});

it('pushes again once the burst is over', function (): void {
    resolve(NotifySharedDocumentAction::class)->handle($this->employee, Document::factory()->create());
    $this->travel(NotifySharedDocumentAction::BURST_MINUTES + 1)->minutes();
    resolve(NotifySharedDocumentAction::class)->handle($this->employee, Document::factory()->create());

    expect(materialPushChannels($this->employee))->toBe([
        ['database', ContentNotification::PUSH_CHANNEL],
        ['database', ContentNotification::PUSH_CHANNEL],
    ]);
});
