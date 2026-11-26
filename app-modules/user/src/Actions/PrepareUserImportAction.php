<?php

declare(strict_types=1);

namespace TresPontosTech\User\Actions;

use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\DTOs\ImportErrorDTO;
use TresPontosTech\User\DTOs\ImportUsersResultDTO;
use TresPontosTech\User\DTOs\PreparedUserImportDTO;
use TresPontosTech\User\Exceptions\DuplicateImportHeadersException;
use TresPontosTech\User\Support\DepartmentLookup;

final readonly class PrepareUserImportAction
{
    public function __construct(
        private ParseUsersFromFileAction $parser,
        private ValidateUserImportAction $validator,
        private ClassifyImportRowsAction $classifier,
        private CheckImportSeatLimitAction $seatLimit,
    ) {}

    public function execute(string $filePath, string $fileExtension, Company $company): PreparedUserImportDTO
    {
        try {
            $rows = $this->parser->execute($filePath, $fileExtension);
        } catch (DuplicateImportHeadersException $duplicateImportHeadersException) {
            return PreparedUserImportDTO::rejected([
                new ImportErrorDTO(row: 1, email: 'N/A', message: $duplicateImportHeadersException->getMessage()),
            ]);
        }

        if ($rows->isEmpty()) {
            return new PreparedUserImportDTO($rows);
        }

        $departments = DepartmentLookup::for($company);
        $errors = $this->validator->execute($rows, $company, $departments);

        if ($errors !== []) {
            return PreparedUserImportDTO::rejected($errors);
        }

        $preview = ImportUsersResultDTO::fromClassifiedRows($this->classifier->execute($rows, $company));
        $seatError = $this->seatLimit->execute($company, $preview->joined());

        if ($seatError instanceof ImportErrorDTO) {
            return PreparedUserImportDTO::rejected([$seatError]);
        }

        return new PreparedUserImportDTO($departments->withDepartmentIds($rows), preview: $preview);
    }
}
