<?php

namespace TresPontosTech\App\Filament\Pages;

use App\Models\Users\User;
use Filament\Auth\Pages\Register;
use Illuminate\Database\Eloquent\Model;
use Override;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

final class UserRegistration extends Register
{
    #[Override]
    protected function handleRegistration(array $data): Model
    {
        /** @var User $user */
        $user = parent::handleRegistration($data);
        event(new UserRegistered($user, Roles::Employee));

        return $user;
    }
}
