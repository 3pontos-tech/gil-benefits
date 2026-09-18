<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches;

use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\PartnersCluster;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Pages\CreateVoucherBatch;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Pages\ListVoucherBatches;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Pages\ViewVoucherBatch;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\RelationManagers\VoucherCodesRelationManager;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Schemas\VoucherBatchForm;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Tables\VoucherBatchesTable;
use TresPontosTech\Vouchers\Models\VoucherBatch;

class VoucherBatchResource extends Resource
{
    protected static ?string $model = VoucherBatch::class;

    protected static ?string $slug = 'voucher-batches';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $cluster = PartnersCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.partners');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.voucher_batches.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::resources.voucher_batches.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::resources.voucher_batches.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return VoucherBatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VoucherBatchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VoucherCodesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVoucherBatches::route('/'),
            'create' => CreateVoucherBatch::route('/create'),
            'view' => ViewVoucherBatch::route('/{record}'),
        ];
    }
}
