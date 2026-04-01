<?php

namespace TresPontosTech\User\Actions;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Role;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\DTOs\ImportErrorDTO;
use TresPontosTech\User\DTOs\ImportUsersResultDTO;

class ImportUsersFromFileAction
{
    private const array REQUIRED_COLUMNS = ['name', 'email', 'document_id', 'tax_id'];

    private const int CHUNK_SIZE = 100;

    public function execute(string $filePath, string $fileExtension, Company $company): ImportUsersResultDTO
    {

        $rows = SimpleExcelReader::create($filePath, $fileExtension)
            ->getRows()
            ->collect();

        if ($rows->isEmpty()) {
            return new ImportUsersResultDTO(imported: 0, errors: []);
        }

        $missingColumns = array_diff(self::REQUIRED_COLUMNS, array_keys($rows->first()));

        if ($missingColumns !== []) {
            return new ImportUsersResultDTO(
                imported: 0,
                errors: [new ImportErrorDTO(
                    row: 1,
                    email: 'N/A',
                    message: 'Estrutura inválida. Colunas ausentes: ' . implode(', ', $missingColumns) . '.',
                )],
            );
        }

        $rows = $rows->reject(fn (array $row): bool => $this->isEmptyRow($row))->values();

        if ($rows->isEmpty()) {
            return new ImportUsersResultDTO(imported: 0, errors: []);
        }

        /** @var list<ImportErrorDTO> $errors */
        $errors = [];

        $rows->each(function (array $row, int $index) use (&$errors): void {
            $rowNumber = $index + 2;
            $name = trim((string) ($row['name'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $documentId = trim((string) ($row['document_id'] ?? '')) ?: null;
            $taxId = trim((string) ($row['tax_id'] ?? '')) ?: null;

            if ($name === '' || $email === '' || $documentId === null || $taxId === null) {
                $errors[] = new ImportErrorDTO(
                    row: $rowNumber,
                    email: $email ?: 'N/A',
                    message: 'Campos obrigatórios ausentes: name, email, document_id e tax_id são obrigatórios.',
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

        $existingEmails = User::query()
            ->whereIn('email', $emails->filter()->unique()->all())
            ->pluck('email');

        $existingEmails->each(function (string $email) use (&$errors): void {
            $errors[] = new ImportErrorDTO(
                row: 0,
                email: $email,
                message: 'Email já cadastrado no sistema.',
            );
        });

        $seatError = $this->checkSeatLimit($company, $rows->count());

        if ($seatError !== null) {
            $errors[] = new ImportErrorDTO(row: 0, email: 'N/A', message: $seatError);
        }

        if ($errors !== []) {
            return new ImportUsersResultDTO(imported: 0, errors: $errors);
        }

        $imported = 0;
        $now = now();
        $roleId = Role::findByName(Roles::Employee->value)->id;
        $userMorphClass = (new User)->getMorphClass();
        $roleTable = config('permission.table_names.model_has_roles');

        $rows->chunk(self::CHUNK_SIZE)->each(
            function (Collection $chunk) use ($company, &$imported, $now, $roleId, $userMorphClass, $roleTable): void {
                DB::transaction(
                    function () use ($chunk, $company, &$imported, $now, $roleId, $userMorphClass, $roleTable): void {
                        $items = $chunk->values()->map(fn (array $row): array => [
                            'id' => (string) Str::uuid(),
                            'row' => $row,
                        ]);

                        User::query()->insert($items->map(fn (array $item): array => [
                            'id' => $item['id'],
                            'name' => trim($item['row']['name']),
                            'email' => strtolower(trim($item['row']['email'])),
                            'password' => bcrypt(Str::password(12)),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->all());

                        Detail::query()->insert($items->map(fn (array $item): array => [
                            'user_id' => $item['id'],
                            'company_id' => $company->getKey(),
                            'document_id' => trim($item['row']['document_id']),
                            'tax_id' => trim($item['row']['tax_id']),
                            'phone_number' => trim($item['row']['phone_number'] ?? '') ?: null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->all());

                        $userIds = $items->pluck('id')->all();

                        $company->employees()->syncWithoutDetaching($userIds);

                        DB::table($roleTable)->insert(
                            collect($userIds)->map(fn (string $id): array => [
                                'role_id' => $roleId,
                                'model_type' => $userMorphClass,
                                'model_id' => $id,
                            ])->all()
                        );

                        $imported += $chunk->count();
                    }
                );
            }
        );

        return new ImportUsersResultDTO(imported: $imported, errors: []);
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->every(fn (mixed $value): bool => trim((string) $value) === '');
    }

    private function checkSeatLimit(Company $company, int $newUsersCount): ?string
    {
        $currentActiveCount = $company->employees()->wherePivot('active', true)->count();

        /** @var CompanyPlan|null $contractualPlan */
        $contractualPlan = $company->activeContractualPlan();

        if ($contractualPlan !== null) {
            $available = $contractualPlan->seats - $currentActiveCount;

            if ($newUsersCount > $available) {
                return sprintf('Limite de assentos excedido. Disponíveis: %s, solicitados: %d.', $available, $newUsersCount);
            }

            return null;
        }

        $activeSubscription = $company->subscriptions()->where('stripe_status', 'active')->first();

        if ($activeSubscription !== null) {
            $available = $activeSubscription->quantity - $currentActiveCount;

            if ($newUsersCount > $available) {
                return sprintf('Limite de assentos excedido. Disponíveis: %s, solicitados: %d.', $available, $newUsersCount);
            }
        }

        return null;
    }
}
