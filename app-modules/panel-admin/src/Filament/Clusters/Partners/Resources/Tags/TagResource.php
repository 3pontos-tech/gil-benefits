<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Clusters\Partners\Resources\Tags;

use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\Tags\Tag;
use TresPontosTech\Admin\Filament\Clusters\Partners\PartnersCluster;
use TresPontosTech\Admin\Filament\Clusters\Partners\Resources\Tags\Pages\CreateTag;
use TresPontosTech\Admin\Filament\Clusters\Partners\Resources\Tags\Pages\EditTag;
use TresPontosTech\Admin\Filament\Clusters\Partners\Resources\Tags\Pages\ListTags;
use TresPontosTech\Admin\Filament\Clusters\Partners\Resources\Tags\Schemas\TagForm;
use TresPontosTech\Admin\Filament\Clusters\Partners\Resources\Tags\Tables\TagsTable;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $cluster = PartnersCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TagsTable::configure($table);
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
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
        ];
    }
}
