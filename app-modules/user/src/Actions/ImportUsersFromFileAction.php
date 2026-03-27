<?php

namespace TresPontosTech\User\Actions;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

class ImportUsersFromFileAction
{
    /**
     * @return array{imported: int, errors: list<array{row: int, email: string, message: string}>}
     */
    public function execute(string $filePath, string $fileExtension, Company $company): array
    {
        $imported = 0;
        $errors = [];
        $rowNumber = 1;

        SimpleExcelReader::create($filePath, $fileExtension)
            ->getRows()
            ->each(function (array $row) use (&$imported, &$errors, &$rowNumber, $company): void {
                ++$rowNumber;

                $name = trim((string) ($row['name'] ?? ''));
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                $documentId = trim((string) ($row['document_id'] ?? '')) ?: null;
                $taxId = trim((string) ($row['tax_id'] ?? '')) ?: null;

                if ($name === '' || $email === '' || $documentId === null || $taxId === null) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'email' => $email ?: 'N/A',
                        'message' => 'Campos obrigatórios ausentes: name, email, document_id e tax_id são obrigatórios.',
                    ];

                    return;
                }

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'email' => $email,
                        'message' => 'Email inválido.',
                    ];

                    return;
                }

                if (User::query()->where('email', $email)->exists()) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'email' => $email,
                        'message' => 'Email já cadastrado.',
                    ];

                    return;
                }

                try {
                    DB::transaction(function () use ($row, $name, $email, $documentId, $taxId, $company): void {
                        $user = User::query()->create([
                            'name' => $name,
                            'email' => $email,
                            'password' => bcrypt(Str::password(12)),
                        ]);

                        $phoneNumber = trim((string) ($row['phone_number'] ?? '')) ?: null;

                        Detail::query()->create([
                            'user_id' => $user->getKey(),
                            'company_id' => $company->getKey(),
                            'phone_number' => $phoneNumber,
                            'document_id' => $documentId,
                            'tax_id' => $taxId,
                        ]);

                        $company->employees()->syncWithoutDetaching($user);
                        $user->assignRole(Roles::Employee);
                    });

                    ++$imported;
                } catch (\Throwable $throwable) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'email' => $email,
                        'message' => 'Erro ao criar usuário: ' . $throwable->getMessage(),
                    ];
                }
            });

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }
}
