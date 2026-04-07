<?php

namespace TresPontosTech\User\Actions;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\DTOs\ImportErrorDTO;

class ValidateUserImportAction
{
    private const array REQUIRED_COLUMNS = ['name', 'email', 'tax_id', 'phone_number'];

    /** @return list<ImportErrorDTO> */
    public function execute(Collection $rows, Company $company): array
    {
        $missingColumns = array_diff(self::REQUIRED_COLUMNS, array_keys($rows->first()));

        if ($missingColumns !== []) {
            return [new ImportErrorDTO(
                row: 1,
                email: 'N/A',
                message: 'Estrutura inválida. Colunas ausentes: ' . implode(', ', $missingColumns) . '.',
            )];
        }

        $errors = [];

        $rows->each(function (array $row, int $index) use (&$errors): void {
            $rowNumber = $row['__row_number'];
            $name = trim((string) ($row['name'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $taxId = trim((string) ($row['tax_id'] ?? '')) ?: null;
            $phoneNumber = trim((string) ($row['phone_number'] ?? '')) ?: null;
            $documentId = trim((string) ($row['document_id'] ?? '')) ?: null;

            if ($documentId !== null) {
                $documentIdDigits = strlen(preg_replace('/[^a-zA-Z0-9]/', '', $documentId));

                if ($documentIdDigits < 5) {
                    $errors[] = new ImportErrorDTO(
                        row: $rowNumber,
                        email: $email ?: 'N/A',
                        message: 'O campo document_id deve ter no mínimo 5 caracteres.',
                    );
                } elseif ($documentIdDigits > 12) {
                    $errors[] = new ImportErrorDTO(
                        row: $rowNumber,
                        email: $email ?: 'N/A',
                        message: 'O campo document_id deve ter no máximo 12 caracteres.',
                    );
                }
            }

            if ($taxId !== null) {
                $taxIdDigits = strlen(preg_replace('/\D/', '', $taxId));

                if ($taxIdDigits < 11) {
                    $errors[] = new ImportErrorDTO(
                        row: $rowNumber,
                        email: $email ?: 'N/A',
                        message: 'O campo tax_id deve ter no mínimo 11 dígitos.',
                    );
                } elseif ($taxIdDigits > 12) {
                    $errors[] = new ImportErrorDTO(
                        row: $rowNumber,
                        email: $email ?: 'N/A',
                        message: 'O campo tax_id deve ter no máximo 12 dígitos.',
                    );
                }
            }

            if ($phoneNumber !== null) {
                $phoneDigits = strlen(preg_replace('/\D/', '', $phoneNumber));

                if ($phoneDigits < 10) {
                    $errors[] = new ImportErrorDTO(
                        row: $rowNumber,
                        email: $email ?: 'N/A',
                        message: 'O campo phone_number deve ter no mínimo 10 dígitos.',
                    );
                } elseif ($phoneDigits > 11) {
                    $errors[] = new ImportErrorDTO(
                        row: $rowNumber,
                        email: $email ?: 'N/A',
                        message: 'O campo phone_number deve ter no máximo 11 dígitos.',
                    );
                }
            }

            $missingFields = array_keys(array_filter([
                'name' => blank($name),
                'email' => blank($email),
                'tax_id' => blank($taxId),
                'phone_number' => blank($phoneNumber),
            ]));

            if ($missingFields !== []) {
                $errors[] = new ImportErrorDTO(
                    row: $rowNumber,
                    email: $email ?: 'N/A',
                    message: 'Campos obrigatórios ausentes: ' . implode(', ', $missingFields) . '.',
                );

                return;
            }

            if (mb_strlen($name) > 255) {
                $errors[] = new ImportErrorDTO(
                    row: $rowNumber,
                    email: $email,
                    message: 'O campo name deve ter no máximo 255 caracteres.',
                );
            }

            if (mb_strlen($email) > 255) {
                $errors[] = new ImportErrorDTO(
                    row: $rowNumber,
                    email: $email,
                    message: 'O campo email deve ter no máximo 255 caracteres.',
                );
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = new ImportErrorDTO(
                    row: $rowNumber,
                    email: $email,
                    message: 'Email inválido.',
                );
            }

        });

        $emails = $rows->map(fn (array $row): string => strtolower(trim((string) ($row['email'] ?? ''))));

        $emails->filter(fn (string $email): bool => filled($email))->duplicates()->each(function (string $email, int $index) use (&$errors, $rows): void {
            $errors[] = new ImportErrorDTO(
                row: $rows[$index]['__row_number'],
                email: $email,
                message: 'Email duplicado na planilha.',
            );
        });

        User::query()
            ->whereIn('email', $emails->filter()->unique()->all())
            ->pluck('email')
            ->each(function (string $email) use (&$errors): void {
                $errors[] = new ImportErrorDTO(
                    row: 0,
                    email: $email,
                    message: 'Email já cadastrado no sistema.',
                );
            });

        $taxIds = $rows->map(fn (array $row): string => trim((string) ($row['tax_id'] ?? '')));

        $taxIds->filter(fn (string $taxId): bool => filled($taxId))->duplicates()->each(function (string $taxId, int $index) use (&$errors, $rows): void {
            $errors[] = new ImportErrorDTO(
                row: $rows[$index]['__row_number'],
                email: 'N/A',
                message: sprintf("tax_id '%s' duplicado na planilha.", $taxId),
            );
        });

        Detail::query()
            ->whereIn('tax_id', $taxIds->filter()->unique()->all())
            ->pluck('tax_id')
            ->each(function (string $taxId) use (&$errors): void {
                $errors[] = new ImportErrorDTO(
                    row: 0,
                    email: 'N/A',
                    message: sprintf("tax_id '%s' já cadastrado no sistema.", $taxId),
                );
            });

        $phoneNumbers = $rows->map(fn (array $row): string => trim((string) ($row['phone_number'] ?? '')));

        $phoneNumbers->filter(fn (string $phone): bool => filled($phone))->duplicates()->each(function (string $phone, int $index) use (&$errors, $rows): void {
            $errors[] = new ImportErrorDTO(
                row: $rows[$index]['__row_number'],
                email: 'N/A',
                message: sprintf("phone_number '%s' duplicado na planilha.", $phone),
            );
        });

        if (filled($errors)) {
            usort($errors, fn (ImportErrorDTO $a, ImportErrorDTO $b): int => $a->row <=> $b->row);

            return $errors;
        }

        $seatError = $this->checkSeatLimit($company, $rows->count());

        if ($seatError !== null) {
            return [new ImportErrorDTO(row: 0, email: 'N/A', message: $seatError)];
        }

        return [];
    }

    private function checkSeatLimit(Company $company, int $newUsersCount): ?string
    {
        $currentActiveCount = $company->employees()->wherePivot('active', true)->count();

        /** @var CompanyPlan|null $contractualPlan */
        $contractualPlan = $company->activeContractualPlan();

        if ($contractualPlan !== null) {
            $available = $contractualPlan->seats - $currentActiveCount;

            if ($newUsersCount > $available) {
                return sprintf('Limite de assentos excedido. Disponíveis: %d, solicitados: %d.', $available, $newUsersCount);
            }

            return null;
        }

        $activeSubscription = $company->subscriptions()->where('stripe_status', 'active')->first();

        if ($activeSubscription !== null) {
            $available = $activeSubscription->quantity - $currentActiveCount;

            if ($newUsersCount > $available) {
                return sprintf('Limite de assentos excedido. Disponíveis: %d, solicitados: %d.', $available, $newUsersCount);
            }
        }

        return null;
    }
}
