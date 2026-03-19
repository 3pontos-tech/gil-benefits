<?php

namespace TresPontosTech\Admin\Filament\Resources\Companies\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Admin\Filament\Resources\Permissions\Actions\AssignRoleAction;
use TresPontosTech\Admin\Filament\Resources\Users\UserResource;
use TresPontosTech\Permissions\Roles;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'employees';

    protected static ?string $relatedResource = UserResource::class;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('panel-admin::resources.companies.relation_managers.employees.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('roles.name')
                    ->label(__('panel-admin::resources.companies.relation_managers.employees.role'))
                    ->formatStateUsing(fn ($state): string => Roles::from($state)->getLabel())
                    ->color(fn ($state): array => Roles::from($state)->getColor())
                    ->badge(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordTitleAttribute('name'),
            ])
            ->recordActions([
                AssignRoleAction::make(),
            ]);
    }
}
