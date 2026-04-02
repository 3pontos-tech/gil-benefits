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
            $rowNumber = $index + 2;
            $name = trim((string) ($row['name'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $taxId = trim((string) ($row['tax_id'] ?? '')) ?: null;
            $phoneNumber = trim((string) ($row['phone_number'] ?? '')) ?: null;

            if ($name === '' || $email === '' || $taxId === null || $phoneNumber === null) {
                $errors[] = new ImportErrorDTO(
                    row: $rowNumber,
                    email: $email ?: 'N/A',
                    message: 'Campos obrigatórios ausentes: name, email, tax_id e phone_number são obrigatórios.',
                );

                return;
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

        $emails->duplicates()->each(function (string $email, int $index) use (&$errors): void {
            $errors[] = new ImportErrorDTO(
                row: $index + 2,
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

        $taxIds->duplicates()->each(function (string $taxId, int $index) use (&$errors): void {
            $errors[] = new ImportErrorDTO(
                row: $index + 2,
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

        if ($errors !== []) {
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
