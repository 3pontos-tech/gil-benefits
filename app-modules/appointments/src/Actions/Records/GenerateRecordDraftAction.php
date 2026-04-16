<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Records;

use Illuminate\Http\UploadedFile;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Exceptions\PrismProviderOverloadedException;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Exceptions\PrismRequestTooLargeException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\ValueObjects\Media\Document;
use Throwable;
use TresPontosTech\Appointments\DTO\GeneratedDraft;
use TresPontosTech\Appointments\Exceptions\RecordGenerationFailedException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Support\AiCircuitBreaker;
use TresPontosTech\Appointments\Support\DocumentTextExtractor;

readonly class GenerateRecordDraftAction
{
    public function __construct(
        private DocumentTextExtractor $extractor,
        private AiCircuitBreaker $circuit,
    ) {}

    public function execute(UploadedFile $file, Appointment $appointment): GeneratedDraft
    {
        $extractedText = $this->extractor->extractText($file);

        $targets = array_values(array_filter([
            $this->resolveTarget('primary'),
            $this->resolveTarget('fallback'),
        ]));

        return $this->callWithFallback($file, $extractedText, $appointment, $targets);
    }

    /**
     * @return array{provider: Provider, model: string}|null
     */
    private function resolveTarget(string $tier): ?array
    {
        $providerName = (string) config(sprintf('appointments.ai.%s.provider', $tier));
        $model = (string) config(sprintf('appointments.ai.%s.model', $tier));

        if ($providerName === '' || $model === '') {
            return null;
        }

        $provider = Provider::tryFrom($providerName);

        if ($provider === null) {
            logger()->error('IA :: provider inválido na configuração', [
                'tier' => $tier,
                'provider' => $providerName,
            ]);

            return null;
        }

        return [
            'provider' => $provider,
            'model' => $model,
        ];
    }

    /**
     * @param  list<array{provider: Provider, model: string}>  $targets
     */
    private function callWithFallback(
        UploadedFile $file,
        ?string $extractedText,
        Appointment $appointment,
        array $targets,
    ): GeneratedDraft {
        $last = null;

        foreach ($targets as $target) {
            $provider = $target['provider'];
            $model = $target['model'];

            if ($this->circuit->isOpen($provider, $model)) {
                continue;
            }

            try {
                return $this->callPrism($file, $extractedText, $appointment, $provider, $model);
            } catch (PrismRateLimitedException $e) {
                $cbKey = $this->circuit->open($provider, $model);
                logger()->warning('IA :: rate limit do provider — abrindo circuit breaker', [
                    'provider' => $provider->value,
                    'model' => $model,
                    'circuit_key' => $cbKey,
                    'cooldown_minutes' => $this->circuit->cooldownMinutes(),
                    'retry_after' => $e->retryAfter ?? null,
                    'message' => $e->getMessage(),
                    'record_id' => $appointment->record?->id,
                ]);
                $last = $e;

                continue;
            } catch (PrismProviderOverloadedException $e) {
                $cbKey = $this->circuit->open($provider, $model);
                logger()->warning('IA :: provider sobrecarregado — abrindo circuit breaker', [
                    'provider' => $provider->value,
                    'model' => $model,
                    'circuit_key' => $cbKey,
                    'cooldown_minutes' => $this->circuit->cooldownMinutes(),
                    'message' => $e->getMessage(),
                    'record_id' => $appointment->record?->id,
                ]);
                $last = $e;

                continue;
            } catch (PrismRequestTooLargeException $e) {
                logger()->warning('IA :: documento maior que o suportado pelo modelo — pulando sem abrir circuit', [
                    'provider' => $provider->value,
                    'model' => $model,
                    'message' => $e->getMessage(),
                    'record_id' => $appointment->record?->id,
                ]);
                $last = $e;

                continue;
            } catch (PrismException $e) {
                logger()->error('IA :: erro permanente do Prism — pulando target', [
                    'provider' => $provider->value,
                    'model' => $model,
                    'exception_class' => $e::class,
                    'message' => $e->getMessage(),
                    'record_id' => $appointment->record?->id,
                ]);
                $last = $e;

                continue;
            } catch (Throwable $e) {
                logger()->error('IA :: erro inesperado durante geração — pulando target', [
                    'provider' => $provider->value,
                    'model' => $model,
                    'exception_class' => $e::class,
                    'message' => $e->getMessage(),
                    'record_id' => $appointment->record?->id,
                ]);
                $last = $e;

                continue;
            }
        }

        logger()->error('IA :: todos os targets falharam — geração de ata abortada', [
            'targets_tried' => array_map(
                fn (array $t): string => sprintf('%s:%s', $t['provider']->value, $t['model']),
                $targets,
            ),
            'record_id' => $appointment->record?->id,
            'last_message' => $last?->getMessage(),
            'last_exception_class' => $last instanceof Throwable ? $last::class : null,
        ]);

        throw RecordGenerationFailedException::allModelsFailed($last);
    }

    private function callPrism(
        UploadedFile $file,
        ?string $extractedText,
        Appointment $appointment,
        Provider $provider,
        string $model,
    ): GeneratedDraft {
        $promptHeader = view('appointments::prompts.record-draft', [
            'appointment' => $appointment,
        ])->render();

        $builder = Prism::structured()
            ->using($provider, $model)
            ->withSchema($this->buildSchema())
            ->withClientOptions([
                'timeout' => (int) config('appointments.ai.timeout', 70),
                'connect_timeout' => (int) config('appointments.ai.connect_timeout', 10),
            ]);

        if ($extractedText !== null) {
            $fullPrompt = $promptHeader
                . "\n\n## Conteúdo do documento ({$file->getClientOriginalName()})\n\n"
                . $extractedText;

            $response = $builder->withPrompt($fullPrompt)->asStructured();
        } else {
            $response = $builder->withPrompt($promptHeader, [
                Document::fromRawContent(
                    rawContent: $file->get(),
                    mimeType: $file->getMimeType(),
                ),
            ])->asStructured();
        }

        $structured = $response->structured ?? [];
        $content = trim((string) ($structured['content'] ?? ''));

        if ($content === '') {
            throw new PrismException('AI returned empty appointment record content.');
        }

        $summary = $structured['internal_summary'] ?? null;

        return new GeneratedDraft(
            content: $content,
            modelUsed: $model,
            inputTokens: $response->usage->promptTokens,
            outputTokens: $response->usage->completionTokens,
            internalSummary: is_string($summary) && trim($summary) !== '' ? $summary : null,
        );
    }

    private function buildSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'appointment_record_draft',
            description: 'Ata estruturada de atendimento de consultoria financeira da Flamma, contendo a ata visível ao cliente e o resumo interno destinado ao próximo consultor.',
            properties: [
                new StringSchema(
                    name: 'content',
                    description: 'Ata completa da reunião em Markdown (pt-BR), começando exatamente pelo cabeçalho obrigatório e seguindo a sequência de seções definida no prompt.',
                ),
                new StringSchema(
                    name: 'internal_summary',
                    description: 'Resumo interno curto em Markdown destinado ao próximo consultor. Deve ser null quando o documento não trouxer informação suficiente para redigir o resumo.',
                    nullable: true,
                ),
            ],
            requiredFields: ['content', 'internal_summary'],
        );
    }
}
