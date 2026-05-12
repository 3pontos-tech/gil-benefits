<?php

namespace TresPontosTech\Admin\Filament\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Admin\Filament\Resources\Users\UserResource;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $plainPassword = null;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->plainPassword = $data['password'] ?? null;

        return $data;
    }

    protected function afterCreate(): void
    {
        event(new UserRegistered($this->record, Roles::User, $this->plainPassword));
    }
}
