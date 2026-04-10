<?php

namespace App\Filament\Shared\Fields;

use Filament\Forms\Components\TextInput;
use Leandrocfe\FilamentPtbrFormFields\Document;

class TaxIdInput
{
    public static function make(): TextInput
    {
        return Document::make('tax_id')
            ->label(__('panel-admin::resources.pages.edit_profile.cpf'))
            ->cpf()
            ->dehydrateMask()
            ->required();
    }
}
