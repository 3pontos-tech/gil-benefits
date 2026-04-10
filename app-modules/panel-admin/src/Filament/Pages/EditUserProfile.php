<?php

namespace TresPontosTech\Admin\Filament\Pages;

use App\Filament\Shared\Fields\DocumentIdInput;
use App\Filament\Shared\Fields\TaxIdInput;
use App\Filament\Shared\Pages\EditUserProfile as BaseEditUserProfile;
use Filament\Schemas\Components\Component;

class EditUserProfile extends BaseEditUserProfile
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
                ->label(__('panel-admin::resources.pages.edit_profile.rg'))
                ->minLength(5)
                ->maxLength(14)
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
