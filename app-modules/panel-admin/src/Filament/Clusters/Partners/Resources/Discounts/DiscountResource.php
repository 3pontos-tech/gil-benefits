<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts;

use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\PartnersCluster;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Pages\CreateDiscount;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Pages\EditDiscount;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Pages\ListDiscounts;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\RelationManagers\GrantsRelationManager;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\RelationManagers\RedemptionsRelationManager;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Schemas\DiscountForm;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Tables\DiscountsTable;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $slug = 'discounts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $cluster = PartnersCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.partners');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.discounts.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::resources.discounts.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::resources.discounts.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return DiscountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            GrantsRelationManager::class,
            RedemptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscounts::route('/'),
            'create' => CreateDiscount::route('/create'),
            'edit' => EditDiscount::route('/{record}/edit'),
        ];
    }
}
