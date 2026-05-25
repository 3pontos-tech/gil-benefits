<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\DepartmentCategories;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use TresPontosTech\Admin\Filament\Resources\DepartmentCategories\Pages\CreateDepartmentCategory;
use TresPontosTech\Admin\Filament\Resources\DepartmentCategories\Pages\EditDepartmentCategory;
use TresPontosTech\Admin\Filament\Resources\DepartmentCategories\Pages\ListDepartmentCategories;
use TresPontosTech\Company\Models\DepartmentCategory;

class DepartmentCategoryResource extends Resource
{
    protected static ?string $model = DepartmentCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.department_categories.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::resources.department_categories.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::resources.department_categories.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('panel-admin::resources.department_categories.form.name'))
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('panel-admin::resources.department_categories.table.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('departments_count')
                    ->label(__('panel-admin::resources.department_categories.table.departments_count'))
                    ->counts('departments')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('panel-admin::resources.department_categories.table.created_at'))
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartmentCategories::route('/'),
            'create' => CreateDepartmentCategory::route('/create'),
            'edit' => EditDepartmentCategory::route('/{record}/edit'),
        ];
    }
}
