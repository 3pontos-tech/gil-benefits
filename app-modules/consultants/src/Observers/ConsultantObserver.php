<?php

namespace TresPontosTech\Consultants\Observers;

use App\Models\Users\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;
use TresPontosTech\User\Support\EmailAddress;

class ConsultantObserver
{
    /**
     * Liga o consultor a um usuário com o mesmo e-mail, criando-o se ainda não existir.
     *
     * A busca usa o e-mail normalizado: os usuários são gravados em minúsculas (#291), e
     * buscar com a capitalização do cadastro do consultor não acharia a conta existente e
     * tentaria criar outra, quebrando no índice único.
     */
    public function created(Consultant $consultant): void
    {
        $user = User::query()->firstOrCreate([
            'email' => EmailAddress::normalize($consultant->email),
        ], [
            'name' => $consultant->name,
            'password' => Str::password(),
        ]);

        $consultant->user()->associate($user)->save();

        $setPasswordUrl = $user->wasRecentlyCreated ? $this->setPasswordUrl($user) : null;
        event(new UserRegistered($user, Roles::Consultant, setPasswordUrl: $setPasswordUrl));
    }

    private function setPasswordUrl(User $user): string
    {
        return URL::signedRoute('filament.consultant.auth.password-reset.reset', [
            'email' => $user->getEmailForPasswordReset(),
            'token' => Password::broker()->createToken($user),
        ]);
    }
}
