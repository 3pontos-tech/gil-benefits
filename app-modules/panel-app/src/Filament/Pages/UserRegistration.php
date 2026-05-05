<?php

namespace TresPontosTech\App\Filament\Pages;

use App\Filament\Shared\Fields\DocumentIdInput;
use App\Filament\Shared\Fields\TaxIdInput;
use App\Models\Users\User;
use Filament\Auth\Pages\Register;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Override;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

final class UserRegistration extends Register
{
    #[Override]
    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            TaxIdInput::make(),
            DocumentIdInput::make(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }

    #[Override]
    protected function handleRegistration(array $data): Model
    {
        /** @var User $user */
        $user = parent::handleRegistration($data);
        event(new UserRegistered($user, Roles::Employee));
        $user->detail()->create([
            'tax_id' => $data['tax_id'],
            'document_id' => $data['document_id'],
        ]);

        return $user;
    }
}
