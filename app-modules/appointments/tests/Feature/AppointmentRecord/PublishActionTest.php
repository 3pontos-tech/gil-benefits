<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Actions\Records\PublishAppointmentRecordAction;
use TresPontosTech\Appointments\Mail\AppointmentRecordPublishedMail;
use TresPontosTech\Appointments\Models\AppointmentRecord;

beforeEach(function (): void {
    Mail::fake();
});

it('publica um rascunho, marca published_at e envia email', function (): void {
    /** @var AppointmentRecord $record */
    $record = AppointmentRecord::factory()->draft()->create();

    expect($record->published_at)->toBeNull();

    resolve(PublishAppointmentRecordAction::class)
        ->execute($record, '## Resumo executivo\n\nConteúdo final da ata.');

    $record->refresh();

    expect($record->published_at)->not->toBeNull()
        ->and($record->isPublished())->toBeTrue()
        ->and($record->content)->toContain('Conteúdo final da ata.');

    Mail::assertQueued(
        AppointmentRecordPublishedMail::class,
        fn (AppointmentRecordPublishedMail $mail): bool => $mail->record->is($record)
            && $mail->hasTo($record->appointment->user->email)
    );
});

it('atualiza o conteúdo de uma ata já publicada sem reenviar email', function (): void {
    /** @var AppointmentRecord $record */
    $record = AppointmentRecord::factory()->published()->create([
        'content' => 'versão antiga',
    ]);

    $originalPublishedAt = $record->published_at;

    Mail::fake();

    resolve(PublishAppointmentRecordAction::class)
        ->execute($record, 'versão revisada');

    $record->refresh();

    expect($record->content)->toBe('versão revisada')
        ->and($record->published_at->equalTo($originalPublishedAt))->toBeTrue();

    Mail::assertNothingQueued();
});
