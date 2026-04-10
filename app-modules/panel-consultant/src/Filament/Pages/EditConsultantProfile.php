<?php

namespace TresPontosTech\Consultants\Filament\Pages;

use App\Filament\Shared\Fields\DocumentIdInput;
use App\Filament\Shared\Fields\TaxIdInput;
use App\Filament\Shared\Pages\EditUserProfile as BaseEditUserProfile;
use Filament\Schemas\Components\Component;

class EditConsultantProfile extends BaseEditUserProfile
{
    /**
     * @return array<Component>
     */
    protected function getExtraDetailFormComponents(): array
    {
        return [
            TaxIdInput::make()
                ->label(__('panel-admin::resources.pages.edit_profile.cpf')),
            DocumentIdInput::make()
                ->minLength(7)
                ->maxLength(14)
                ->required()
                ->unique(
                    table: 'user_details',
                    column: 'document_id',
                    ignorable: $this->getUser()->detail,
                    ignoreRecord: false
                ),
        ];
    }

    /**
     * @return array<string>
     */
    protected function getDetailFields(): array
    {
        return array_merge(parent::getDetailFields(), ['tax_id', 'document_id']);
    }
}
