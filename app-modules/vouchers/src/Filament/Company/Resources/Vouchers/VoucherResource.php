<?php

namespace TresPontosTech\Vouchers\Filament\Company\Resources\Vouchers;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use TresPontosTech\Vouchers\Filament\Company\Resources\Vouchers\Pages\ListVouchers;
use TresPontosTech\Vouchers\Filament\Company\Resources\Vouchers\Schemas\VoucherForm;
use TresPontosTech\Vouchers\Filament\Company\Resources\Vouchers\Tables\VouchersTable;
use TresPontosTech\Vouchers\Models\Voucher;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Ticket;

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
        ];
    }
}
