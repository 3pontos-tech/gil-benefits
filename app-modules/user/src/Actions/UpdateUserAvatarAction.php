<?php

declare(strict_types=1);

namespace TresPontosTech\User\Actions;

use App\Models\Users\User;
use Illuminate\Http\UploadedFile;

final readonly class UpdateUserAvatarAction
{
    /**
     * Troca a foto de perfil, ou remove quando `$avatar` é nulo.
     *
     * A coleção `user_avatar` é singleFile: a nova foto substitui a anterior sozinha.
     */
    public function handle(User $user, ?UploadedFile $avatar): User
    {
        if (! $avatar instanceof UploadedFile) {
            $user->clearMediaCollection('user_avatar');

            return $user->load('media');
        }

        $user->addMedia($avatar)->toMediaCollection('user_avatar');

        return $user->load('media');
    }
}
