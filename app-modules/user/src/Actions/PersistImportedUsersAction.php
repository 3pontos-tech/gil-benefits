<?php

namespace TresPontosTech\User\Actions;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Role;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\DTOs\ImportUsersResultDTO;
use TresPontosTech\User\Mail\WelcomeUserMail;

class PersistImportedUsersAction
{
    private const int CHUNK_SIZE = 100;

    public function execute(Collection $rows, Company $company): ImportUsersResultDTO
    {
        $imported = 0;
        $now = now();
        $roleId = Role::findByName(Roles::Employee->value)->id;
        $userMorphClass = (new User)->getMorphClass();
        $roleTable = config('permission.table_names.model_has_roles');

        $rows->chunk(self::CHUNK_SIZE)->each(
            function (Collection $chunk) use ($company, &$imported, $now, $roleId, $userMorphClass, $roleTable): void {
                $items = $chunk->values()->map(fn (array $row): array => [
                    'id' => (string) Str::uuid(),
                    'plain_password' => Str::password(12),
                    'row' => $row,
                ]);

                $items = $items->map(fn (array $item): array => array_merge($item, [
                    'hashed_password' => bcrypt($item['plain_password']),
                ]));

                DB::transaction(
                    function () use ($items, $company, &$imported, $now, $roleId, $userMorphClass, $roleTable): void {
                        User::query()->insert($items->map(fn (array $item): array => [
                            'id' => $item['id'],
                            'name' => trim($item['row']['name']),
                            'email' => strtolower(trim($item['row']['email'])),
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

                        $userIds = $items->pluck('id')->all();

                        $company->employees()->syncWithoutDetaching($userIds);

                        DB::table($roleTable)->insert(
                            collect($userIds)->map(fn (string $id): array => [
                                'role_id' => $roleId,
                                'model_type' => $userMorphClass,
                                'model_id' => $id,
                            ])->all()
                        );

                        $imported += $items->count();
                    }
                );

                $passwordsByUserId = $items->pluck('plain_password', 'id');

                User::query()->whereIn('id', $items->pluck('id'))->each(
                    function (User $user) use ($passwordsByUserId): void {
                        try {
                            Mail::to($user->email)->queue(new WelcomeUserMail($user, $passwordsByUserId[$user->id]));
                        } catch (\Throwable $throwable) {
                            Log::warning('Failed to queue welcome email', [
                                'user_id' => $user->id,
                                'error' => $throwable->getMessage(),
                            ]);
                        }
                    }
                );
            }
        );

        return new ImportUsersResultDTO(imported: $imported, errors: []);
    }
}
