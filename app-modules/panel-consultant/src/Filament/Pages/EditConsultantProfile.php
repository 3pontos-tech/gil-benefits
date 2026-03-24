<?php

namespace TresPontosTech\Consultants\Filament\Pages;

use App\Filament\Shared\Pages\EditUserProfile as BaseEditUserProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\RawJs;

class EditConsultantProfile extends BaseEditUserProfile
{
    /**
     * @return array<Component>
     */
    protected function getExtraDetailFormComponents(): array
    {
        return [
            TextInput::make('tax_id')
                ->label(__('panel-admin::resources.pages.edit_profile.cpf'))
                ->required()
                ->mask('999.999.999-99'),
            TextInput::make('document_id')
                ->mask(RawJs::make(<<<'JS'
                    $input.replace(/\D/g, '').length > 9
                        ? '999.999.999-99'
                        : '99.999.999-9'
                    JS
                ))
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
