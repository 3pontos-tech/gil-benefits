<?php

namespace TresPontosTech\PanelAdmin\Filament\Resources\Companies\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\PanelAdmin\Filament\Actions\GrantExtraCreditAction;
use TresPontosTech\PanelAdmin\Filament\Resources\Permissions\Actions\AssignRoleAction;
use TresPontosTech\PanelAdmin\Filament\Resources\Users\UserResource;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Concerns\ChecksImportCompletion;
use TresPontosTech\User\Filament\Actions\ImportUsersAction;

class EmployeesRelationManager extends RelationManager
{
    use ChecksImportCompletion;

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
                TextColumn::make('pivot.role')
                    ->label(__('panel-admin::resources.companies.relation_managers.employees.role'))
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state): ?string => $state instanceof Roles
                        ? $state->getLabel()
                        : (filled($state) ? Roles::from($state)->getLabel() : null))
                    ->color(fn ($state): string|array => $state instanceof Roles
                        ? $state->getColor()
                        : (filled($state) ? Roles::from($state)->getColor() : 'gray')),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordTitleAttribute('name')
                    ->mutateDataUsing(function (array $data): array {
                        $data['role'] = Roles::Employee->value;

                        return $data;
                    }),
                ImportUsersAction::make()
                    ->company(fn (): Model => $this->getOwnerRecord()),
            ])
            ->recordActions([
                GrantExtraCreditAction::forEmployee(),
                AssignRoleAction::make()
                    ->company(fn (): Model => $this->getOwnerRecord()),
            ]);
    }
}
