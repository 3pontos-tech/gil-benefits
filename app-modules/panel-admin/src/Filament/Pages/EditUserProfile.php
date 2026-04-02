<?php

namespace TresPontosTech\Admin\Filament\Pages;

use App\Filament\Shared\Pages\EditUserProfile as BaseEditUserProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\RawJs;

class EditUserProfile extends BaseEditUserProfile
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
                ->label(__('panel-admin::resources.pages.edit_profile.rg'))
                ->mask(RawJs::make(<<<'JS'
                    $input.replace(/[^a-zA-Z0-9]/g, '').length > 9
                        ? '***.***.***-**'
                        : '**.***.***-*'
                    JS))
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
