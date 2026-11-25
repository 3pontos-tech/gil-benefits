<?php

namespace TresPontosTech\User\Actions;

use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\DTOs\ImportErrorDTO;
use TresPontosTech\User\DTOs\ImportUsersResultDTO;
use TresPontosTech\User\Exceptions\DuplicateImportHeadersException;
use TresPontosTech\User\Support\DepartmentLookup;

readonly class ImportUsersFromFileAction
{
    public function __construct(
        private ParseUsersFromFileAction $parser,
        private ValidateUserImportAction $validator,
        private ClassifyImportRowsAction $classifier,
        private CheckImportSeatLimitAction $seatLimit,
        private PersistImportedUsersAction $persister,
    ) {}

    public function execute(string $filePath, string $fileExtension, Company $company): ImportUsersResultDTO
    {
        try {
            $rows = $this->parser->execute($filePath, $fileExtension);
        } catch (DuplicateImportHeadersException $duplicateImportHeadersException) {
            return new ImportUsersResultDTO(imported: 0, errors: [
                new ImportErrorDTO(row: 1, email: 'N/A', message: $duplicateImportHeadersException->getMessage()),
            ]);
        }

        if ($rows->isEmpty()) {
            return new ImportUsersResultDTO(imported: 0, errors: []);
        }

        $departments = DepartmentLookup::for($company);
        $errors = $this->validator->execute($rows, $company, $departments);

        if ($errors !== []) {
            return new ImportUsersResultDTO(imported: 0, errors: $errors);
        }

        $preview = ImportUsersResultDTO::fromClassifiedRows($this->classifier->execute($rows, $company));
        $seatError = $this->seatLimit->execute($company, $preview->joined());

        if ($seatError instanceof ImportErrorDTO) {
            return new ImportUsersResultDTO(imported: 0, errors: [$seatError]);
        }

        return $this->persister->execute($departments->withDepartmentIds($rows), $company);
    }
}
