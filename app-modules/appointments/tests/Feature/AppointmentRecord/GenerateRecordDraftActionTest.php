<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Exceptions\PrismProviderOverloadedException;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Exceptions\PrismRequestTooLargeException;
use Prism\Prism\PrismManager;
use Prism\Prism\Structured\Request as StructuredRequest;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\Testing\PrismFake;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\ValueObjects\Usage;
use TresPontosTech\Appointments\Actions\Records\GenerateRecordDraftAction;
use TresPontosTech\Appointments\Exceptions\RecordGenerationFailedException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Appointments\Support\DocumentTextExtractor;

/**
 * Helper que estende PrismFake para suportar exceções na fila de respostas estruturadas.
 *
 * @param  array<int, StructuredResponse|Throwable>  $responses
 */
function fakePrismWithExceptions(array $responses): PrismFake
{
    $fake = new class($responses) extends PrismFake
    {
        public function structured(StructuredRequest $request): StructuredResponse
        {
            $this->recorded[] = $request;

            $next = $this->responses[$this->responseSequence] ?? null;
            ++$this->responseSequence;

            throw_if($next instanceof Throwable, $next);

            if ($next instanceof StructuredResponse) {
                return $next;
            }

            return StructuredResponseFake::make()->withStructured(['content' => 'default fake']);
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

function fakePdfFile(): UploadedFile
{
    $file = Mockery::mock(UploadedFile::class);
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

it('pula provider inválido no primário e executa o fallback sem propagar ValueError', function (): void {
    config([
        'appointments.ai.primary.provider' => 'invalid-provider-xyz',
        'appointments.ai.primary.model' => 'gemini-2.5-pro',
        'appointments.ai.fallback.provider' => 'gemini',
        'appointments.ai.fallback.model' => 'gemini-2.5-flash',
    ]);

    fakePrismWithExceptions([
        StructuredResponseFake::make()->withStructured([
            'content' => 'Conteúdo do fallback',
            'internal_summary' => null,
        ]),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)
        ->execute(fakePdfFile(), $appointment);

    expect($draft->modelUsed)->toBe('gemini-2.5-flash')
        ->and($draft->content)->toBe('Conteúdo do fallback');

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $msg, array $ctx = []): bool => $msg === 'IA :: provider inválido na configuração'
            && ($ctx['tier'] ?? null) === 'primary'
            && ($ctx['provider'] ?? null) === 'invalid-provider-xyz')
        ->once();
});

it('gera ata com sucesso usando o modelo primário (PDF)', function (): void {
    fakePrismWithExceptions([
        StructuredResponseFake::make()->withStructured([
            'content' => "## Resumo executivo\n\nConteúdo gerado.",
            'internal_summary' => null,
        ]),
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
        StructuredResponseFake::make()
            ->withStructured([
                'content' => 'Conteúdo do fallback',
                'internal_summary' => null,
            ])
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
        StructuredResponseFake::make()->withStructured([
            'content' => 'Conteúdo do fallback',
            'internal_summary' => null,
        ]),
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
        StructuredResponseFake::make()->withStructured([
            'content' => 'Conteúdo do fallback',
            'internal_summary' => null,
        ]),
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
        StructuredResponseFake::make()->withStructured([
            'content' => 'Conteúdo do fallback',
            'internal_summary' => null,
        ]),
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
        StructuredResponseFake::make()
            ->withStructured([
                'content' => 'Conteúdo gerado.',
                'internal_summary' => null,
            ])
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

it('popula content e internalSummary a partir dos campos estruturados', function (): void {
    $ata = <<<'MD'
# ATA DE REUNIÃO – Atendimento Flamma

## Resumo da Reunião
Conteúdo da ata completa.
MD;

    $resumo = <<<'MD'
## Resumo para o próximo atendimento

**Contexto rápido**: cliente engajado.

### Pontos-chave
- ponto 1
- ponto 2
MD;

    fakePrismWithExceptions([
        StructuredResponseFake::make()->withStructured([
            'content' => $ata,
            'internal_summary' => $resumo,
        ]),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)
        ->execute(fakePdfFile(), $appointment);

    expect($draft->content)->toBe($ata)
        ->and($draft->internalSummary)->toBe($resumo);
});

it('quando internal_summary vier null, o GeneratedDraft recebe internalSummary null', function (): void {
    fakePrismWithExceptions([
        StructuredResponseFake::make()->withStructured([
            'content' => "# Ata\n\nConteúdo completo.",
            'internal_summary' => null,
        ]),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)
        ->execute(fakePdfFile(), $appointment);

    expect($draft->content)->toContain('Conteúdo completo.')
        ->and($draft->internalSummary)->toBeNull();
});

it('quando internal_summary vier string vazia, o GeneratedDraft normaliza para null', function (): void {
    fakePrismWithExceptions([
        StructuredResponseFake::make()->withStructured([
            'content' => '# Ata',
            'internal_summary' => '   ',
        ]),
    ]);

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)
        ->execute(fakePdfFile(), $appointment);

    expect($draft->internalSummary)->toBeNull();
});

it('usa o DocumentTextExtractor para extrair texto de DOCX e enviar como prompt', function (): void {
    fakePrismWithExceptions([
        StructuredResponseFake::make()->withStructured([
            'content' => 'Conteúdo gerado a partir de DOCX',
            'internal_summary' => null,
        ]),
    ]);

    // Substitui o extractor por uma instância anônima que retorna texto fixo
    app()->instance(DocumentTextExtractor::class, new readonly class extends DocumentTextExtractor
    {
        public function extractText(UploadedFile $file): string
        {
            return 'texto extraído do docx';
        }
    });

    $file = Mockery::mock(UploadedFile::class);
    $file->shouldReceive('getMimeType')->andReturn('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    $file->shouldReceive('getClientOriginalName')->andReturn('notas.docx');

    $appointment = Appointment::factory()->create();
    AppointmentRecord::factory()->recycle($appointment)->draft()->create();
    $appointment->refresh()->load('record');

    $draft = resolve(GenerateRecordDraftAction::class)->execute($file, $appointment);

    expect($draft->content)->toBe('Conteúdo gerado a partir de DOCX');
});
