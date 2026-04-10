<?php

namespace App\Filament\Shared\Fields;

use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

final class DocumentIdInput
{
    public static function make(): TextInput
    {
        return TextInput::make('document_id')
            ->label(__('panel-admin::resources.users.form.document_id'))
            ->mask(RawJs::make(<<<'JS'
                                $input.replace(/[^a-zA-Z0-9]/g, '').length > 9
                                    ? '***.***.***-**'
                                    : '**.***.***-*'
                            JS
            ))
            ->minLength(5)
            ->maxLength(14)
            ->dehydrateStateUsing(fn ($state): string|array|null => preg_replace('/\D/', '', $state));
    }
}
