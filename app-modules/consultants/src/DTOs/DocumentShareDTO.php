<?php

namespace TresPontosTech\Consultants\DTOs;

final readonly class DocumentShareDTO
{
    public function __construct(
        public string|int $documentId,
        public string|int $employeeId,
        public string|int $consultantId,
    ) {}

    public static function make(array $data): self
    {
        return new self(
            documentId: $data['document_id'],
            employeeId: $data['employee_id'],
            consultantId: $data['consultant_id'],
        );
    }
}
