<?php

namespace App\Filament\Shared\Fields;

use Filament\Forms\Components\TextInput;

class TaxIdInput
{
    public static function make(): TextInput
    {
        return TextInput::make('tax_id')
            ->label(__('panel-admin::resources.pages.edit_profile.cpf'))
            ->required()
            ->mask('999.999.999-99')
            ->dehydrateStateUsing(fn ($state): string|array|null => preg_replace('/\D/', '', $state));
    }
}
