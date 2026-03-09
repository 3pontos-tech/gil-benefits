<?php

namespace TresPontosTech\Admin\Filament\Resources\Companies\Pages;

use App\Models\Users\User;
use Filament\Resources\Pages\CreateRecord;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Admin\Filament\Resources\Companies\CompanyResource;
use TresPontosTech\Permissions\Roles;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected function afterCreate(): void
    {
        $this->record->employees()->sync([
            $this->record->user_id => [
                'active' => true,
            ],
        ]);
        $owner = User::query()->find($this->record->user_id);

        $owner->assignRole(Roles::CompanyOwner);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['integration_access_key'] = Uuid::uuid4();

        return $data;
    }
}
