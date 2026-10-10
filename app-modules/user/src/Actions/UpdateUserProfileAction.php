<?php

declare(strict_types=1);

namespace TresPontosTech\User\Actions;

use App\Models\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use TresPontosTech\User\Exceptions\ProfileUpdateException;

final readonly class UpdateUserProfileAction
{
    /**
     * Atualiza os dados cadastrais: nome e e-mail em `users`, telefone em `user_details`.
     *
     * Só toca nos campos presentes em `$data`, então um PATCH de um campo não apaga os outros.
     * O telefone exige um `detail` existente, porque a tabela pede o CPF, que só o cadastro
     * coleta; usuários criados pela API de tenant ainda não têm.
     *
     * @param  array{name?: string, email?: string, phone_number?: string|null}  $data
     *
     * @throws ProfileUpdateException
     */
    public function handle(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->update(Arr::only($data, ['name', 'email']));

            if (array_key_exists('phone_number', $data)) {
                $detail = $user->detail;

                throw_if($detail === null, ProfileUpdateException::missingDetail());

                $detail->update(['phone_number' => $data['phone_number']]);
            }

            return $user;
        });
    }
}
