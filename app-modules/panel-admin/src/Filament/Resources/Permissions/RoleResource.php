<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\Permissions;

use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Admin\Filament\Clusters\Management\ManagementCluster;
use TresPontosTech\Admin\Filament\Resources\Permissions\Pages\CreateRole;
use TresPontosTech\Admin\Filament\Resources\Permissions\Pages\EditRole;
use TresPontosTech\Admin\Filament\Resources\Permissions\Pages\ListRoles;
use TresPontosTech\Admin\Filament\Resources\Permissions\Schemas\RoleForm;
use TresPontosTech\Admin\Filament\Resources\Permissions\Schemas\RoleInfolist;
use TresPontosTech\Admin\Filament\Resources\Permissions\Tables\RolesTable;
use TresPontosTech\Permissions\Role;
use TresPontosTech\Permissions\Roles;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $slug = 'roles';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    protected static ?string $cluster = ManagementCluster::class;

    protected static ?int $navigationSort = 2;

    public static function can(string $action, ?Model $record = null): bool
    {
        return auth()->check() && auth()->user()->hasRole(Roles::SuperAdmin);
    }

    public static function getModelLabel(): string
    {
        return __('permissions::filament.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('permissions::filament.resource.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('permissions::filament.resource.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RoleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
