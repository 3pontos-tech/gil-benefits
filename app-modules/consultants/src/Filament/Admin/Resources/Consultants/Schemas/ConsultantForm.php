<?php

namespace TresPontosTech\Consultants\Filament\Admin\Resources\Consultants\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ConsultantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('crm_id'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('short_description')
                    ->required(),
                Textarea::make('biography')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('readme')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('socials_urls')
                    ->required()
                    ->default('[]')
                    ->columnSpanFull(),
            ]);
    }
}
