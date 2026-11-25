<?php

namespace TresPontosTech\User\Actions;

use Illuminate\Support\Collection;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\User\DTOs\ImportErrorDTO;
use TresPontosTech\User\Support\DepartmentLookup;
use TresPontosTech\User\Support\EmailAddress;

class ValidateUserImportAction
{
    private const array REQUIRED_COLUMNS = ['name', 'email', 'tax_id'];

    /** @var list<ImportErrorDTO> */
    private array $errors = [];

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<ImportErrorDTO>
     */
    public function execute(Collection $rows, Company $company, ?DepartmentLookup $departments = null): array
    {
        $this->errors = [];

        $missingColumns = array_diff(self::REQUIRED_COLUMNS, array_keys($rows->first()));

        if ($missingColumns !== []) {
            return [new ImportErrorDTO(
                row: 1,
                email: 'N/A',
                message: 'Estrutura inválida. Colunas ausentes: ' . implode(', ', $missingColumns) . '.',
            )];
        }

        $rows->each(function (array $row): void {
            $rowNumber = $row['__row_number'];
            $name = trim((string) ($row['name'] ?? ''));
            $email = EmailAddress::normalize((string) ($row['email'] ?? ''));
            $taxId = trim((string) ($row['tax_id'] ?? '')) ?: null;
            $phoneNumber = trim((string) ($row['phone_number'] ?? '')) ?: null;
            $documentId = trim((string) ($row['document_id'] ?? '')) ?: null;

            $this->validateDocumentId($rowNumber, $email, $documentId);
            $this->validateTaxIdFormat($rowNumber, $email, $taxId);
            $this->validatePhoneNumberFormat($rowNumber, $email, $phoneNumber);

            if (! $this->validateRequiredFields($rowNumber, $email, $name, $taxId)) {
                return;
            }

            $this->validateName($rowNumber, $email, $name);
            $this->validateEmail($rowNumber, $email);
        });

        $this->validateDuplicatePhoneNumbers($rows);
        $this->validateDepartments($rows, $departments ?? DepartmentLookup::for($company));

        return $this->sortedErrors();
    }

    private function validateDocumentId(int $rowNumber, string $email, ?string $documentId): void
    {
        if ($documentId === null) {
            return;
        }

        $digits = strlen(preg_replace('/[^a-zA-Z0-9]/', '', $documentId));

        if ($digits < 5) {
            $this->errors[] = new ImportErrorDTO(
                row: $rowNumber,
                email: $email ?: 'N/A',
                message: 'O campo document_id deve ter no mínimo 5 caracteres.',
            );
        } elseif ($digits > 12) {
            $this->errors[] = new ImportErrorDTO(
                row: $rowNumber,
                email: $email ?: 'N/A',
                message: 'O campo document_id deve ter no máximo 12 caracteres.',
            );
        }
    }

    private function validateTaxIdFormat(int $rowNumber, string $email, ?string $taxId): void
    {
        if ($taxId === null) {
            return;
        }

        $digits = strlen(preg_replace('/\D/', '', $taxId));

        if ($digits < 11) {
            $this->errors[] = new ImportErrorDTO(
                row: $rowNumber,
                email: $email ?: 'N/A',
                message: 'O campo tax_id deve ter no mínimo 11 dígitos.',
            );
        } elseif ($digits > 12) {
            $this->errors[] = new ImportErrorDTO(
                row: $rowNumber,
                email: $email ?: 'N/A',
                message: 'O campo tax_id deve ter no máximo 12 dígitos.',
            );
        }
    }

    private function validatePhoneNumberFormat(int $rowNumber, string $email, ?string $phoneNumber): void
    {
        if ($phoneNumber === null) {
            return;
        }

        $digits = strlen(preg_replace('/\D/', '', $phoneNumber));

        if ($digits < 10) {
            $this->errors[] = new ImportErrorDTO(
                row: $rowNumber,
                email: $email ?: 'N/A',
                message: 'O campo phone_number deve ter no mínimo 10 dígitos.',
            );
        } elseif ($digits > 11) {
            $this->errors[] = new ImportErrorDTO(
                row: $rowNumber,
                email: $email ?: 'N/A',
                message: 'O campo phone_number deve ter no máximo 11 dígitos.',
            );
        }
    }

    private function validateRequiredFields(int $rowNumber, string $email, string $name, ?string $taxId): bool
    {
        $missingFields = array_keys(array_filter([
            'name' => blank($name),
            'email' => blank($email),
            'tax_id' => blank($taxId),
        ]));

        if ($missingFields === []) {
            return true;
        }

        $this->errors[] = new ImportErrorDTO(
            row: $rowNumber,
            email: $email ?: 'N/A',
            message: 'Campos obrigatórios ausentes: ' . implode(', ', $missingFields) . '.',
        );

        return false;
    }

    private function validateName(int $rowNumber, string $email, string $name): void
    {
        if (mb_strlen($name) > 255) {
            $this->errors[] = new ImportErrorDTO(
                row: $rowNumber,
                email: $email,
                message: 'O campo name deve ter no máximo 255 caracteres.',
            );
        }
    }

    private function validateEmail(int $rowNumber, string $email): void
    {
        if (mb_strlen($email) > 255) {
            $this->errors[] = new ImportErrorDTO(
                row: $rowNumber,
                email: $email,
                message: 'O campo email deve ter no máximo 255 caracteres.',
            );
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = new ImportErrorDTO(
                row: $rowNumber,
                email: $email,
                message: 'Email inválido.',
            );
        }
    }

    /**
     * Skips rows already repeated by e-mail or CPF: the same person listed twice usually repeats
     * the phone too, and that row is ignored instead of blocking the spreadsheet.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function validateDuplicatePhoneNumbers(Collection $rows): void
    {
        $phoneNumbers = $rows
            ->diffKeys(ClassifyImportRowsAction::sheetDuplicateKeys($rows))
            ->map(fn (array $row): string => trim((string) ($row['phone_number'] ?? '')));

        $phoneNumbers->filter(fn (string $phone): bool => filled($phone))
            ->duplicates()
            ->each(function (string $phone, int $index) use ($rows): void {
                $this->errors[] = new ImportErrorDTO(
                    row: $rows[$index]['__row_number'],
                    email: 'N/A',
                    message: sprintf("phone_number '%s' duplicado na planilha.", $phone),
                );
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function validateDepartments(Collection $rows, DepartmentLookup $lookup): void
    {
        $rowsWithDepartment = $rows->reject(fn (array $row): bool => DepartmentLookup::isBlank($row['department'] ?? ''));

        if ($rowsWithDepartment->isEmpty()) {
            return;
        }

        $hasUnknownDepartment = false;

        $rowsWithDepartment->each(function (array $row) use ($lookup, &$hasUnknownDepartment): void {
            $department = trim((string) $row['department']);
            $matches = $lookup->find($department);

            if (count($matches) === 1) {
                return;
            }

            $hasUnknownDepartment = $hasUnknownDepartment || $matches === [];

            $this->errors[] = new ImportErrorDTO(
                row: $row['__row_number'],
                email: EmailAddress::normalize((string) ($row['email'] ?? '')) ?: 'N/A',
                message: $matches === []
                    ? sprintf("Departamento '%s' não encontrado.", $department)
                    : sprintf(
                        "Departamento '%s' corresponde a mais de um departamento cadastrado: %s.",
                        $department,
                        implode(', ', array_map(fn (Department $match): string => $match->name, $matches)),
                    ),
            );
        });

        if ($hasUnknownDepartment) {
            $this->errors[] = new ImportErrorDTO(
                row: 0,
                email: 'N/A',
                message: $this->registeredDepartmentsMessage($lookup),
            );
        }
    }

    private function registeredDepartmentsMessage(DepartmentLookup $lookup): string
    {
        $names = $lookup->names();

        if ($names === []) {
            return 'A empresa não tem departamentos cadastrados. Deixe a coluna department em branco ou cadastre os departamentos antes de importar.';
        }

        return 'Departamentos cadastrados na empresa: ' . implode(', ', $names) . '.';
    }

    /** @return list<ImportErrorDTO> */
    private function sortedErrors(): array
    {
        usort($this->errors, fn (ImportErrorDTO $a, ImportErrorDTO $b): int => $a->row <=> $b->row);

        return $this->errors;
    }
}
