<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use App\Enums\VoucherStatusEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('consultant_id')
                    ->relationship('consultant', 'name'),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('status')
                    ->options(VoucherStatusEnum::class)
                    ->required(),
                DateTimePicker::make('valid_until')
                    ->required(),
            ]);
    }
}
