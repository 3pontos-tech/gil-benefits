<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Exceptions\PrismException;
use TresPontosTech\Appointments\Actions\Records\GenerateRecordDraftAction;
use TresPontosTech\Appointments\DTO\GeneratedDraft;
use TresPontosTech\Appointments\Exceptions\RecordGenerationFailedException;
use TresPontosTech\Appointments\Jobs\GenerateAppointmentRecordJob;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Consultants\Models\Consultant;

beforeEach(function (): void {
    Log::spy();
    Mail::fake();
    Storage::fake('local');
});

function fakeAppointmentWithRecord(): array
{
    $consultant = Consultant::factory()->create();
    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->getKey(),
    ]);

    /** @var AppointmentRecord $record */
    $record = AppointmentRecord::factory()
        ->recycle($appointment)
        ->draft()
        ->create();

    return [$record, $consultant];
}

function persistFixtureFile(AppointmentRecord $record): string
{
    $path = "appointments/records/{$record->getKey()}.pdf";
    Storage::disk('local')->put($path, '%PDF fake bytes');

    return $path;
}

it('handle: persiste content gerado, marca generation_started_at e deleta o arquivo do disco', function (): void {
    [$record] = fakeAppointmentWithRecord();
    $path = persistFixtureFile($record);

    app()->instance(GenerateRecordDraftAction::class, new readonly class extends GenerateRecordDraftAction
    {
        public function __construct() {}

        public function execute(UploadedFile $file, Appointment $appointment): GeneratedDraft
        {
            return new GeneratedDraft(
                content: '## Resumo executivo\n\nConteúdo persistido pelo job.',
                modelUsed: 'gemini-2.5-pro',
                inputTokens: 12450,
                outputTokens: 2110,
                internalSummary: '## Resumo para o próximo atendimento\n\n- ponto-chave do próximo consultor',
            );
        }
    });

    $job = new GenerateAppointmentRecordJob($record->id, 'local', $path);
    $job->handle(resolve(GenerateRecordDraftAction::class));

    $record->refresh();

    expect($record->content)->toContain('Conteúdo persistido pelo job.')
        ->and($record->internal_summary)->toContain('ponto-chave do próximo consultor')
        ->and($record->model_used)->toBe('gemini-2.5-pro')
        ->and($record->input_tokens)->toBe(12450)
        ->and($record->output_tokens)->toBe(2110)
        ->and($record->generation_started_at)->not->toBeNull();

    Storage::disk('local')->assertMissing($path);

    Mail::assertNothingQueued();
});

it('handle: ignora retry quando generation_started_at já está setado', function (): void {
    [$record] = fakeAppointmentWithRecord();
    $record->update(['generation_started_at' => now()->subMinute()]);
    $path = persistFixtureFile($record);

    $tracker = new stdClass;
    $tracker->called = false;

    app()->instance(GenerateRecordDraftAction::class, new readonly class($tracker) extends GenerateRecordDraftAction
    {
        public function __construct(private stdClass $tracker) {}

        public function execute(UploadedFile $file, Appointment $appointment): GeneratedDraft
        {
            $this->tracker->called = true;

            return new GeneratedDraft(content: '', modelUsed: '', inputTokens: 0, outputTokens: 0);
        }
    });

    $job = new GenerateAppointmentRecordJob($record->id, 'local', $path);
    $job->handle(resolve(GenerateRecordDraftAction::class));

    expect($tracker->called)->toBeFalse();

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $msg): bool => $msg === 'IA :: geração já iniciada, ignorando retry')
        ->once();
});

it('handle: faz rollback do generation_started_at quando a geração falha', function (): void {
    [$record] = fakeAppointmentWithRecord();
    $path = persistFixtureFile($record);

    app()->instance(GenerateRecordDraftAction::class, new readonly class extends GenerateRecordDraftAction
    {
        public function __construct() {}

        public function execute(UploadedFile $file, Appointment $appointment): GeneratedDraft
        {
            throw new PrismException('rede caiu');
        }
    });

    $job = new GenerateAppointmentRecordJob($record->id, 'local', $path);

    expect(fn () => $job->handle(resolve(GenerateRecordDraftAction::class)))
        ->toThrow(PrismException::class);

    $record->refresh();

    expect($record->generation_started_at)->toBeNull();
});

it('failed: loga erro mas NÃO apaga o record', function (): void {
    [$record] = fakeAppointmentWithRecord();

    $job = new GenerateAppointmentRecordJob($record->id, 'local', "appointments/records/{$record->getKey()}.pdf");

    $job->failed(RecordGenerationFailedException::unreadableDocument('reuniao.docx'));

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $msg): bool => $msg === 'IA :: job de geração falhou definitivamente após retries')
        ->once();

    expect(AppointmentRecord::withTrashed()->find($record->id))->not->toBeNull();

    Mail::assertNothingQueued();
});
