<?php

namespace TresPontosTech\PanelAdmin\Filament\Resources\Users\Pages;

use App\Models\Users\User;
use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\PanelAdmin\Filament\Resources\Users\UserResource;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

/**
 * @property-read User $record
 */
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
