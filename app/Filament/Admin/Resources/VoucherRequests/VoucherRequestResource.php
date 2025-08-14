<?php

namespace App\Filament\Admin\Resources\VoucherRequests;

use App\Filament\Admin\Resources\VoucherRequests\Pages\CreateVoucherRequest;
use App\Filament\Admin\Resources\VoucherRequests\Pages\EditVoucherRequest;
use App\Filament\Admin\Resources\VoucherRequests\Pages\ListVoucherRequests;
use App\Filament\Admin\Resources\VoucherRequests\Schemas\VoucherRequestForm;
use App\Filament\Admin\Resources\VoucherRequests\Tables\VoucherRequestsTable;
use App\Models\VoucherRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VoucherRequestResource extends Resource
{
    protected static ?string $model = VoucherRequest::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Administration';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::InboxArrowDown;

    public static function form(Schema $schema): Schema
    {
        return VoucherRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VoucherRequestsTable::configure($table);
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
            'index' => ListVoucherRequests::route('/'),
            'create' => CreateVoucherRequest::route('/create'),
            'edit' => EditVoucherRequest::route('/{record}/edit'),
        ];
    }
}
