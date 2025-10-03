<?php

namespace TresPontosTech\Vouchers\Filament\Company\Resources\Vouchers\Schemas;

use App\Models\Users\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Vouchers\Enums\VoucherStatusEnum;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('code')
                    ->default(Uuid::uuid4()->toString()),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('consultant_id')
                    ->relationship('consultant', 'name'),
                Select::make('user_id')
                    ->options(User::query()->pluck('name', 'id'))
                    ->relationship('user', 'name'),
                Select::make('status')
                    ->options(VoucherStatusEnum::class)
                    ->required(),
                DateTimePicker::make('valid_until')
                    ->required(),
            ]);
    }
}
