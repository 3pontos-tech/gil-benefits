<?php

namespace TresPontosTech\Admin\Filament\Pages;

use App\Filament\Shared\Pages\EditUserProfile as BaseEditUserProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

class EditUserProfile extends BaseEditUserProfile
{
    /**
     * @return array<Component>
     */
    protected function getExtraDetailFormComponents(): array
    {
        return [
            TextInput::make('tax_id')
                ->label('CPF')
                ->mask('999.999.999-99'),
            TextInput::make('document_id')
                ->label('RG')
                ->mask('99.999.999-9'),
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
