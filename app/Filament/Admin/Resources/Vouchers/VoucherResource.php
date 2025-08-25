<?php

namespace App\Filament\Admin\Resources\Vouchers;

use App\Filament\Admin\Resources\Vouchers\Pages\CreateVoucher;
use App\Filament\Admin\Resources\Vouchers\Pages\EditVoucher;
use App\Filament\Admin\Resources\Vouchers\Pages\ListVouchers;
use App\Filament\Admin\Resources\Vouchers\Schemas\VoucherForm;
use App\Filament\Admin\Resources\Vouchers\Tables\VouchersTable;
use App\Models\Voucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Products';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Ticket;

    public static function form(Schema $schema): Schema
    {
        return VoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VouchersTable::configure($table);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
