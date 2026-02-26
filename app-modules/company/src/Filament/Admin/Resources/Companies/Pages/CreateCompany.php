<?php

namespace TresPontosTech\Company\Filament\Admin\Resources\Companies\Pages;

use Filament\Resources\Pages\CreateRecord;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Filament\Admin\Resources\Companies\CompanyResource;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected function afterCreate(): void
    {
        $this->record->employees()->sync([
            $this->record->user_id => [
                'role' => CompanyRoleEnum::Owner->value,
                'active' => true,
            ],
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['integration_access_key'] = Uuid::uuid4();

        return $data;
    }
}
