<?php

namespace TresPontosTech\Admin\Filament\Resources\Users;

use App\Models\Users\User;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TresPontosTech\Admin\Filament\Clusters\Management\ManagementCluster;
use TresPontosTech\Admin\Filament\Resources\Users\Pages\CreateUser;
use TresPontosTech\Admin\Filament\Resources\Users\Pages\EditUser;
use TresPontosTech\Admin\Filament\Resources\Users\Pages\ListUsers;
use TresPontosTech\Admin\Filament\Resources\Users\Schemas\UserForm;
use TresPontosTech\Admin\Filament\Resources\Users\Tables\UsersTable;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserCircle;
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = ManagementCluster::class;


    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
