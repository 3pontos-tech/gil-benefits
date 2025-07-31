<?php

namespace App\Filament\App\Resources\Vouchers;

use App\Filament\App\Resources\Vouchers\Pages\CreateVoucher;
use App\Filament\App\Resources\Vouchers\Pages\EditVoucher;
use App\Filament\App\Resources\Vouchers\Pages\ListVouchers;
use App\Filament\App\Resources\Vouchers\Schemas\VoucherForm;
use App\Filament\App\Resources\Vouchers\Tables\VouchersTable;
use App\Models\Voucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return VoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VouchersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVouchers::route('/'),
            'create' => CreateVoucher::route('/create'),
            'edit' => EditVoucher::route('/{record}/edit'),
        ];
    }
}
