<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\DTO;

final readonly class GeneratedDraft
{
    public function __construct(
        public string $content,
        public string $modelUsed,
        public int $inputTokens,
        public int $outputTokens,
        public ?string $internalSummary = null,
    ) {}
}
