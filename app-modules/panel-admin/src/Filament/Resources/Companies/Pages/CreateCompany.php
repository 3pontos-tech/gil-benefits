<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages;

use Filament\Resources\Pages\CreateRecord;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\CompanyResource;
use TresPontosTech\Permissions\Roles;

/**
 * @property-read Company $record
 */
class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected function afterCreate(): void
    {
        // Owner role lives in the pivot (and is also derived from companies.user_id).
        $this->record->employees()->sync([
            $this->record->user_id => [
                'role' => Roles::CompanyOwner->value,
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
