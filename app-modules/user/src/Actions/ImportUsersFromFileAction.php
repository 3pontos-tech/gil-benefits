<?php

namespace TresPontosTech\User\Actions;

use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\DTOs\ImportUsersResultDTO;

readonly class ImportUsersFromFileAction
{
    public function __construct(
        private PrepareUserImportAction $preparer,
        private PersistImportedUsersAction $persister,
    ) {}

    public function execute(string $filePath, string $fileExtension, Company $company): ImportUsersResultDTO
    {
        $prepared = $this->preparer->execute($filePath, $fileExtension, $company);

        if ($prepared->isRejected() || $prepared->isEmpty()) {
            return new ImportUsersResultDTO(imported: 0, errors: $prepared->errors);
        }

        return $this->persister->execute($prepared->rows, $company);
    }
}
