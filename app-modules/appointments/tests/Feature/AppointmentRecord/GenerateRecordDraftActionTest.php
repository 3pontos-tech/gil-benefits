<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Exceptions\PrismProviderOverloadedException;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Exceptions\PrismRequestTooLargeException;
use Prism\Prism\PrismManager;
use Prism\Prism\Testing\PrismFake;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\Text\Request as TextRequest;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Usage;
use TresPontosTech\Appointments\Actions\Records\GenerateRecordDraftAction;
use TresPontosTech\Appointments\Exceptions\RecordGenerationFailedException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Appointments\Support\DocumentTextExtractor;

/**
 * Helper que estende PrismFake para suportar exceções na fila de respostas.
 *
 * @param  array<int, TextResponse|Throwable>  $responses
 */
function fakePrismWithExceptions(array $responses): PrismFake
{
    $fake = new class($responses) extends PrismFake
    {
        public function text(TextRequest $request): TextResponse
        {
            $this->recorded[] = $request;

            $next = $this->responses[$this->responseSequence] ?? null;
            ++$this->responseSequence;

            throw_if($next instanceof Throwable, $next);

            if ($next instanceof TextResponse) {
                return $next;
            }

            return TextResponseFake::make()->withText('default fake');
        }
    };

    app()->instance(PrismManager::class, new class($fake) extends PrismManager
    {
        public function __construct(private readonly PrismFake $fake) {}

        public function resolve(Provider|string $name, array $providerConfig = []): PrismFake
        {
            $this->fake->setProviderConfig($providerConfig);

            return $this->fake;
        }
    });

    return $fake;
}

function fakePdfFile(): TemporaryUploadedFile
{
    $file = Mockery::mock(TemporaryUploadedFile::class);
    $file->shouldReceive('getMimeType')->andReturn('application/pdf');
    $file->shouldReceive('get')->andReturn('%PDF-1.4 fake bytes');
    $file->shouldReceive('getClientOriginalName')->andReturn('reuniao.pdf');

    return $file;
}

beforeEach(function (): void {
    Cache::flush();
    Log::spy();

    config([
        'appointments.ai.primary.provider' => 'gemini',
        'appointments.ai.primary.model' => 'gemini-2.5-pro',
        'appointments.ai.fallback.provider' => 'gemini',
        'appointments.ai.fallback.model' => 'gemini-2.5-flash',
        'appointments.ai.circuit_cooldown_minutes' => 3,
    ]);
});

it('gera ata com sucesso usando o modelo primário (PDF)', function (): void {
    fakePrismWithExceptions([
        TextResponseFake::make()->withText('## Resumo executivo\n\nConteúdo gerado.'),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)
        ->execute(fakePdfFile(), $appointment);

    expect($draft->modelUsed)->toBe('gemini-2.5-pro')
        ->and($draft->content)->toContain('Conteúdo gerado.');

    expect(Cache::has('appointments:ai:cb:gemini:gemini-2.5-pro'))->toBeFalse();
});

it('faz fallback ao modelo secundário quando o primário sofre rate limit', function (): void {
    fakePrismWithExceptions([
        new PrismRateLimitedException(rateLimits: [], retryAfter: 30),
        TextResponseFake::make()
            ->withText('Conteúdo do fallback')
            ->withUsage(new Usage(promptTokens: 5000, completionTokens: 800)),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)
        ->execute(fakePdfFile(), $appointment);

    expect($draft->modelUsed)->toBe('gemini-2.5-flash')
        ->and($draft->content)->toBe('Conteúdo do fallback')
        ->and($draft->inputTokens)->toBe(5000)
        ->and($draft->outputTokens)->toBe(800);

    expect(Cache::has('appointments:ai:cb:gemini:gemini-2.5-pro'))->toBeTrue()
        ->and(Cache::has('appointments:ai:cb:gemini:gemini-2.5-flash'))->toBeFalse();

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $msg, array $ctx = []): bool => $msg === 'IA :: rate limit do provider — abrindo circuit breaker'
            && ($ctx['model'] ?? null) === 'gemini-2.5-pro')
        ->once();
});

it('abre o circuit breaker quando o provider está sobrecarregado', function (): void {
    fakePrismWithExceptions([
        new PrismProviderOverloadedException('gemini'),
        TextResponseFake::make()->withText('Conteúdo do fallback'),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    resolve(GenerateRecordDraftAction::class)->execute(fakePdfFile(), $appointment);

    expect(Cache::has('appointments:ai:cb:gemini:gemini-2.5-pro'))->toBeTrue();

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $msg): bool => $msg === 'IA :: provider sobrecarregado — abrindo circuit breaker')
        ->once();
});

it('NÃO abre o circuit breaker quando o documento é grande demais', function (): void {
    fakePrismWithExceptions([
        new PrismRequestTooLargeException('gemini'),
        TextResponseFake::make()->withText('Conteúdo do fallback'),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    resolve(GenerateRecordDraftAction::class)->execute(fakePdfFile(), $appointment);

    expect(Cache::has('appointments:ai:cb:gemini:gemini-2.5-pro'))->toBeFalse();

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $msg): bool => $msg === 'IA :: documento maior que o suportado pelo modelo — pulando sem abrir circuit')
        ->once();
});

it('pula o modelo se o circuit breaker estiver aberto', function (): void {
    Cache::put('appointments:ai:cb:gemini:gemini-2.5-pro', true, now()->addMinutes(3));

    $fake = fakePrismWithExceptions([
        TextResponseFake::make()->withText('Conteúdo do fallback'),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)
        ->execute(fakePdfFile(), $appointment);

    expect($draft->modelUsed)->toBe('gemini-2.5-flash');

    $fake->assertCallCount(1);
});

it('lança RecordGenerationFailedException quando todos os modelos falham', function (): void {
    fakePrismWithExceptions([
        new PrismException('falha primário'),
        new PrismException('falha fallback'),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    expect(fn () => resolve(GenerateRecordDraftAction::class)
        ->execute(fakePdfFile(), $appointment))
        ->toThrow(RecordGenerationFailedException::class);

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $msg): bool => $msg === 'IA :: todos os targets falharam — geração de ata abortada')
        ->once();
});

it('persiste input e output tokens vindos do Usage do Prism no GeneratedDraft', function (): void {
    fakePrismWithExceptions([
        TextResponseFake::make()
            ->withText('Conteúdo gerado.')
            ->withUsage(new Usage(promptTokens: 12450, completionTokens: 2110)),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)
        ->execute(fakePdfFile(), $appointment);

    expect($draft->inputTokens)->toBe(12450)
        ->and($draft->outputTokens)->toBe(2110)
        ->and($draft->modelUsed)->toBe('gemini-2.5-pro');
});

it('separa ata e resumo interno quando a resposta contém o delimitador', function (): void {
    $fullResponse = <<<'MD'
# ATA DE REUNIÃO – Atendimento Flamma

## Resumo da Reunião
Conteúdo da ata completa.

---INTERNAL_SUMMARY---

## Resumo para o próximo atendimento

**Contexto rápido**: cliente engajado.

### Pontos-chave
- ponto 1
- ponto 2
MD;

    fakePrismWithExceptions([
        TextResponseFake::make()->withText($fullResponse),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)
        ->execute(fakePdfFile(), $appointment);

    expect($draft->content)->toContain('# ATA DE REUNIÃO')
        ->and($draft->content)->toContain('Conteúdo da ata completa.')
        ->and($draft->content)->not->toContain('---INTERNAL_SUMMARY---')
        ->and($draft->content)->not->toContain('Resumo para o próximo atendimento')
        ->and($draft->internalSummary)->not->toBeNull()
        ->and($draft->internalSummary)->toContain('## Resumo para o próximo atendimento')
        ->and($draft->internalSummary)->toContain('ponto 1')
        ->and($draft->internalSummary)->not->toContain('Conteúdo da ata completa.');
});

it('remove envelope ```markdown ...``` que o modelo possa ter adicionado em volta da resposta', function (): void {
    $fullResponse = <<<'MD'
```markdown
# ATA DE REUNIÃO – Atendimento Flamma

## Resumo da Reunião
Conteúdo real da ata.

---INTERNAL_SUMMARY---

## Resumo para o próximo atendimento
- ponto-chave
```
MD;

    fakePrismWithExceptions([
        TextResponseFake::make()->withText($fullResponse),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)
        ->execute(fakePdfFile(), $appointment);

    expect($draft->content)->toContain('# ATA DE REUNIÃO')
        ->and($draft->content)->not->toContain('```')
        ->and($draft->content)->not->toContain('markdown')
        ->and($draft->internalSummary)->not->toBeNull()
        ->and($draft->internalSummary)->toContain('ponto-chave')
        ->and($draft->internalSummary)->not->toContain('```');
});

it('quando a resposta não tem delimitador, content recebe tudo e internalSummary fica null', function (): void {
    fakePrismWithExceptions([
        TextResponseFake::make()->withText('# Ata sem delimitador\n\nConteúdo completo.'),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)
        ->execute(fakePdfFile(), $appointment);

    expect($draft->content)->toContain('Ata sem delimitador')
        ->and($draft->internalSummary)->toBeNull();
});

it('usa o DocumentTextExtractor para extrair texto de DOCX e enviar como prompt', function (): void {
    fakePrismWithExceptions([
        TextResponseFake::make()->withText('Conteúdo gerado a partir de DOCX'),
    ]);

    // Substitui o extractor por uma instância anônima que retorna texto fixo
    app()->instance(DocumentTextExtractor::class, new readonly class extends DocumentTextExtractor
    {
        public function extractText(TemporaryUploadedFile $file): string
        {
            return 'texto extraído do docx';
        }
    });

    $file = Mockery::mock(TemporaryUploadedFile::class);
    $file->shouldReceive('getMimeType')->andReturn('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    $file->shouldReceive('getClientOriginalName')->andReturn('notas.docx');

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)->execute($file, $appointment);

    expect($draft->content)->toBe('Conteúdo gerado a partir de DOCX');
});
