<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Exceptions;

use RuntimeException;
use Throwable;

final class RecordGenerationFailedException extends RuntimeException
{
    public const REASON_ALL_MODELS_FAILED = 'all_models_failed';

    public const REASON_UNREADABLE_DOCUMENT = 'unreadable_document';

    public const REASON_UNSUPPORTED_MIME_TYPE = 'unsupported_mime_type';

    public function __construct(
        string $message,
        public readonly string $reason,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function allModelsFailed(?Throwable $previous = null): self
    {
        return new self(
            message: 'Todos os modelos de IA falharam ao gerar a ata.',
            reason: self::REASON_ALL_MODELS_FAILED,
            previous: $previous,
        );
    }

    public static function unreadableDocument(string $filename, ?Throwable $previous = null): self
    {
        return new self(
            message: sprintf('Não foi possível ler o documento "%s". Verifique se não está corrompido ou protegido por senha.', $filename),
            reason: self::REASON_UNREADABLE_DOCUMENT,
            previous: $previous,
        );
    }

    public static function unsupportedMimeType(string $mime): self
    {
        return new self(
            message: sprintf('Tipo de arquivo não suportado: %s. Use PDF, DOC ou DOCX.', $mime),
            reason: self::REASON_UNSUPPORTED_MIME_TYPE,
        );
    }
}
