<?php

namespace TresPontosTech\User\Actions;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Tenant\Exceptions\EmployedElsewhere;
use TresPontosTech\Tenant\Models\TenantMember;
use TresPontosTech\User\DTOs\ClassifiedImportRowDTO;
use TresPontosTech\User\DTOs\ImportUsersResultDTO;
use TresPontosTech\User\Enums\ImportRowOutcome;
use TresPontosTech\User\Events\ExistingUserImported;
use TresPontosTech\User\Events\UserImported;
use TresPontosTech\User\Mail\WelcomeUserMail;
use TresPontosTech\User\Support\EmailAddress;

class PersistImportedUsersAction
{
    private const int CHUNK_SIZE = 100;

    private const int TEMPORARY_PASSWORD_ROUNDS = 4;

    public function __construct(
        private readonly ClassifyImportRowsAction $classifier,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function execute(Collection $rows, Company $company): ImportUsersResultDTO
    {
        $classified = $this->classifier->execute($rows, $company);

        $settled = $classified->reject(fn (ClassifiedImportRowDTO $row): bool => $row->outcome->joinsCompany())->values();

        $classified
            ->filter(fn (ClassifiedImportRowDTO $row): bool => $row->outcome->joinsCompany())
            ->map(fn (ClassifiedImportRowDTO $row): array => $row->row)
            ->chunk(self::CHUNK_SIZE)
            ->each(function (Collection $chunk) use ($company, &$settled): void {
                $settled = $settled->merge($this->persistChunk($chunk->values(), $company));
            });

        return ImportUsersResultDTO::fromClassifiedRows($settled);
    }

    /**
     * Someone can be created, or hired elsewhere, between the classification and the write. The
     * transaction rolls back and the chunk is classified and written once more with that person
     * in place.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, ClassifiedImportRowDTO>
     */
    private function persistChunk(Collection $rows, Company $company): Collection
    {
        try {
            return $this->writeChunk($rows, $company);
        } catch (UniqueConstraintViolationException|EmployedElsewhere) {
            return $this->writeChunk($rows, $company);
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, ClassifiedImportRowDTO>
     */
    private function writeChunk(Collection $rows, Company $company): Collection
    {
        /** @var array{0: Collection<int, ClassifiedImportRowDTO>, 1: Collection<string, string>} $written */
        $written = DB::transaction(function () use ($rows, $company): array {
            $classified = $this->classifier->execute($rows, $company);

            $passwordsByUserId = $this->createUsers(
                $classified->filter(fn (ClassifiedImportRowDTO $row): bool => $row->outcome === ImportRowOutcome::Create),
                $company,
            );

            $classified
                ->filter(fn (ClassifiedImportRowDTO $row): bool => $row->outcome === ImportRowOutcome::Link)
                ->each(fn (ClassifiedImportRowDTO $row) => $company->employees()->attach($row->userId, [
                    'role' => Roles::Employee->value,
                    'department_id' => $row->row['department_id'] ?? null,
                ]));

            $classified
                ->filter(fn (ClassifiedImportRowDTO $row): bool => $row->outcome === ImportRowOutcome::Reactivate)
                ->each(fn (ClassifiedImportRowDTO $row) => $this->reactivate($row));

            return [$classified, $passwordsByUserId];
        });

        [$classified, $passwordsByUserId] = $written;

        $this->announceCreated($passwordsByUserId);
        $this->announceExisting($classified);

        return $classified;
    }

    /**
     * @param  Collection<int, ClassifiedImportRowDTO>  $rows
     * @return Collection<string, string> the temporary password of each created user, by id
     */
    private function createUsers(Collection $rows, Company $company): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        $now = now();

        $items = $rows->values()->map(function (ClassifiedImportRowDTO $row): array {
            $plainPassword = Str::password(12);

            return [
                'id' => (string) Str::uuid(),
                'plain_password' => $plainPassword,
                'hashed_password' => bcrypt($plainPassword, ['rounds' => self::TEMPORARY_PASSWORD_ROUNDS]),
                'row' => $row->row,
            ];
        });

        User::query()->insert($items->map(fn (array $item): array => [
            'id' => $item['id'],
            'name' => trim($item['row']['name']),
            'email' => EmailAddress::normalize($item['row']['email']),
            'password' => $item['hashed_password'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        Detail::query()->insert($items->map(fn (array $item): array => [
            'user_id' => $item['id'],
            'company_id' => $company->getKey(),
            'document_id' => trim($item['row']['document_id'] ?? '') ?: null,
            'tax_id' => trim($item['row']['tax_id']),
            'phone_number' => trim($item['row']['phone_number'] ?? '') ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        $company->employees()->syncWithoutDetaching(
            $items->mapWithKeys(fn (array $item): array => [
                $item['id'] => [
                    'role' => Roles::Employee->value,
                    'department_id' => $item['row']['department_id'] ?? null,
                ],
            ])->all()
        );

        return $items->pluck('plain_password', 'id');
    }

    /**
     * The department in the row replaces the old one; a blank cell keeps it.
     */
    private function reactivate(ClassifiedImportRowDTO $row): void
    {
        $membership = TenantMember::query()->findOrFail($row->membershipId);
        $membership->active = true;

        if (filled($row->row['department_id'] ?? null)) {
            $membership->department_id = $row->row['department_id'];
        }

        $membership->save();
    }

    /**
     * @param  Collection<string, string>  $passwordsByUserId
     */
    private function announceCreated(Collection $passwordsByUserId): void
    {
        if ($passwordsByUserId->isEmpty()) {
            return;
        }

        User::query()->whereIn('id', $passwordsByUserId->keys())->each(
            function (User $user) use ($passwordsByUserId): void {
                try {
                    Mail::to($user->email)->queue(new WelcomeUserMail($user, $passwordsByUserId[$user->id]));
                } catch (\Throwable $throwable) {
                    Log::warning('Failed to queue welcome email', [
                        'user_id' => $user->id,
                        'error' => $throwable->getMessage(),
                    ]);
                }

                try {
                    event(new UserImported($user));
                } catch (\Throwable $throwable) {
                    Log::warning('Failed to handle imported user', [
                        'user_id' => $user->id,
                        'error' => $throwable->getMessage(),
                    ]);
                }
            }
        );
    }

    /**
     * @param  Collection<int, ClassifiedImportRowDTO>  $classified
     */
    private function announceExisting(Collection $classified): void
    {
        $userIds = $classified
            ->filter(fn (ClassifiedImportRowDTO $row): bool => in_array($row->outcome, [ImportRowOutcome::Link, ImportRowOutcome::Reactivate], true))
            ->map(fn (ClassifiedImportRowDTO $row): ?string => $row->userId)
            ->filter();

        if ($userIds->isEmpty()) {
            return;
        }

        User::query()->whereIn('id', $userIds->all())->each(function (User $user): void {
            try {
                event(new ExistingUserImported($user));
            } catch (\Throwable $throwable) {
                Log::warning('Failed to handle existing user joined by import', [
                    'user_id' => $user->id,
                    'error' => $throwable->getMessage(),
                ]);
            }
        });
    }
}
