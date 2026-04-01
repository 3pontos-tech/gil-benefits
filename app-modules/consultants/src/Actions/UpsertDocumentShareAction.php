<?php

namespace TresPontosTech\Consultants\Actions;

use TresPontosTech\Consultants\DTOs\DocumentShareDTO;
use TresPontosTech\Consultants\Models\DocumentShare;

final class UpsertDocumentShareAction
{
    public function execute(DocumentShareDTO $dto): void
    {
        DocumentShare::query()->updateOrCreate([
            'document_id' => $dto->documentId,
            'employee_id' => $dto->employeeId,
            'consultant_id' => $dto->consultantId,
        ]);
    }
}
