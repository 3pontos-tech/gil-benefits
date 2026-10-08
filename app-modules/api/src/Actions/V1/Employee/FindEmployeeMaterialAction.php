<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use TresPontosTech\Api\Support\EmployeeMaterials;
use TresPontosTech\Consultants\Models\Document;

final readonly class FindEmployeeMaterialAction
{
    public function __construct(private EmployeeMaterials $materials) {}

    /**
     * Material que a pessoa enxerga; qualquer outro (de outra pessoa, inativo, excluído)
     * responde como inexistente.
     */
    public function handle(User $user, string $documentId): Document
    {
        return $this->materials->visibleTo($user)->whereKey($documentId)->firstOrFail();
    }
}
