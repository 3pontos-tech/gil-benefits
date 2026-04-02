<?php

namespace TresPontosTech\User\Actions;

use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\DTOs\ImportUsersResultDTO;

readonly class ImportUsersFromFileAction
{
    public function __construct(
        private ParseUsersFromFileAction $parser,
        private ValidateUserImportAction $validator,
        private PersistImportedUsersAction $persister,
    ) {}

    public function execute(string $filePath, string $fileExtension, Company $company): ImportUsersResultDTO
    {
        $rows = $this->parser->execute($filePath, $fileExtension);

        if ($rows->isEmpty()) {
            return new ImportUsersResultDTO(imported: 0, errors: []);
        }

        $errors = $this->validator->execute($rows, $company);

        if ($errors !== []) {
            return new ImportUsersResultDTO(imported: 0, errors: $errors);
        }

        return $this->persister->execute($rows, $company);
    }
}
