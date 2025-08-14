<?php

namespace App\Filament\Company\Resources\VoucherRequests\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VoucherRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name', function ($query): void {
                        $query->whereIn('id', auth()->user()->companies()->pluck('id'));
                    })
                    ->required(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                Hidden::make('status')
                    ->default('pending')
                    ->required(),
            ]);
    }
}
