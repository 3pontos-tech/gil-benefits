<?php

declare(strict_types=1);

namespace TresPontosTech\User\Actions;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Tenant\Models\TenantMember;
use TresPontosTech\User\DTOs\ClassifiedImportRowDTO;
use TresPontosTech\User\Enums\ImportRowOutcome;
use TresPontosTech\User\Support\EmailAddress;

class ClassifyImportRowsAction
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, ClassifiedImportRowDTO>
     */
    public function execute(Collection $rows, Company $company): Collection
    {
        $duplicates = self::sheetDuplicateKeys($rows);

        $emails = $rows->map(fn (array $row): string => self::emailOf($row))->filter()->unique()->values();
        $taxIds = $rows->map(fn (array $row): string => self::taxIdOf($row))->filter()->unique()->values();

        $usersByEmail = User::query()->withTrashed()
            ->whereIn('email', $emails->all())
            ->get(['id', 'email', 'deleted_at'])
            ->keyBy('email');

        $detailsByTaxId = Detail::query()->withTrashed()
            ->whereIn('tax_id', $taxIds->all())
            ->get(['id', 'user_id', 'tax_id', 'deleted_at'])
            ->keyBy('tax_id');

        $usersById = User::query()->withTrashed()
            ->whereIn('id', $usersByEmail->pluck('id')->merge($detailsByTaxId->pluck('user_id'))->unique()->all())
            ->get(['id', 'deleted_at'])
            ->keyBy('id');

        $membershipsHere = TenantMember::query()
            ->where('company_id', $company->getKey())
            ->whereIn('user_id', $usersById->keys()->all())
            ->whereNull('deleted_at')
            ->get(['id', 'user_id', 'role', 'active'])
            ->keyBy('user_id');

        $employedElsewhere = array_flip(TenantMember::employedElsewhere(
            array_values(array_map(strval(...), $usersById->keys()->all())),
            (string) $company->getKey(),
        ));

        $seenUserIds = [];

        return $rows->map(function (array $row, int $index) use ($duplicates, $usersByEmail, $detailsByTaxId, $usersById, $membershipsHere, $employedElsewhere, &$seenUserIds): ClassifiedImportRowDTO {
            if (isset($duplicates[$index])) {
                return new ClassifiedImportRowDTO($row, ImportRowOutcome::DuplicateInSheet);
            }

            /** @var User|null $userByEmail */
            $userByEmail = $usersByEmail->get(self::emailOf($row));
            /** @var Detail|null $detailByTaxId */
            $detailByTaxId = $detailsByTaxId->get(self::taxIdOf($row));

            if (! $userByEmail instanceof User && ! $detailByTaxId instanceof Detail) {
                return new ClassifiedImportRowDTO($row, ImportRowOutcome::Create);
            }

            if ($userByEmail instanceof User && $detailByTaxId instanceof Detail && $userByEmail->getKey() !== $detailByTaxId->user_id) {
                return new ClassifiedImportRowDTO($row, ImportRowOutcome::ConflictingData);
            }

            $userId = (string) ($userByEmail?->getKey() ?? $detailByTaxId?->user_id);

            if (isset($seenUserIds[$userId])) {
                return new ClassifiedImportRowDTO($row, ImportRowOutcome::DuplicateInSheet, $userId);
            }

            $seenUserIds[$userId] = true;

            /** @var User|null $user */
            $user = $usersById->get($userId);

            if (! $user instanceof User || $user->trashed() || $detailByTaxId?->trashed()) {
                return new ClassifiedImportRowDTO($row, ImportRowOutcome::DeletedAccount);
            }

            /** @var TenantMember|null $membership */
            $membership = $membershipsHere->get($userId);

            if ($membership instanceof TenantMember && ($membership->active || $membership->role !== Roles::Employee)) {
                return new ClassifiedImportRowDTO($row, ImportRowOutcome::AlreadyMember, $userId);
            }

            if (isset($employedElsewhere[$userId])) {
                return new ClassifiedImportRowDTO($row, ImportRowOutcome::EmployedElsewhere, $userId);
            }

            return $membership instanceof TenantMember
                ? new ClassifiedImportRowDTO($row, ImportRowOutcome::Reactivate, $userId, (string) $membership->getKey())
                : new ClassifiedImportRowDTO($row, ImportRowOutcome::Link, $userId);
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<int, true>
     */
    public static function sheetDuplicateKeys(Collection $rows): array
    {
        $seenEmails = [];
        $seenTaxIds = [];
        $duplicates = [];

        foreach ($rows as $index => $row) {
            $email = self::emailOf($row);
            $taxId = self::taxIdOf($row);

            if (($email !== '' && isset($seenEmails[$email])) || ($taxId !== '' && isset($seenTaxIds[$taxId]))) {
                $duplicates[$index] = true;

                continue;
            }

            if ($email !== '') {
                $seenEmails[$email] = true;
            }

            if ($taxId !== '') {
                $seenTaxIds[$taxId] = true;
            }
        }

        return $duplicates;
    }

    /** @param  array<string, mixed>  $row */
    private static function emailOf(array $row): string
    {
        return EmailAddress::normalize((string) ($row['email'] ?? ''));
    }

    /** @param  array<string, mixed>  $row */
    private static function taxIdOf(array $row): string
    {
        return trim((string) ($row['tax_id'] ?? ''));
    }
}
