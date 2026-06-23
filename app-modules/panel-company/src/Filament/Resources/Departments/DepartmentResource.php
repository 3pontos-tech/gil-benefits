<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Resources\Departments;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use TresPontosTech\Company\Enums\DepartmentCategory;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelCompany\Filament\Resources\Departments\Pages\CreateDepartment;
use TresPontosTech\PanelCompany\Filament\Resources\Departments\Pages\EditDepartment;
use TresPontosTech\PanelCompany\Filament\Resources\Departments\Pages\ListDepartments;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('all.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-company::resources.departments.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel-company::resources.departments.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-company::resources.departments.plural_model_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Filament::getTenant()?->getKey());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('panel-company::resources.departments.form.name'))
                ->required()
                ->maxLength(255)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', Filament::getTenant()?->getKey()),
                ),
            Select::make('category')
                ->label(__('panel-company::resources.departments.form.category'))
                ->options(DepartmentCategory::class)
                ->searchable()
                ->native(false)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('panel-company::resources.departments.table.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label(__('panel-company::resources.departments.table.category'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('panel-company::resources.departments.table.created_at'))
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
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Filament::getTenant()?->getKey();

        return $data;
    }
}
