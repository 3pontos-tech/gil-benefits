<?php

namespace TresPontosTech\Company\Filament\Admin\Resources\Companies\RelationManagers;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\AttachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use TresPontosTech\Company\Enums\CompanyRoleEnum;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'employees';

    protected static ?string $relatedResource = UserResource::class;

    protected static ?string $title = 'Members';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('pivot.role')
                    ->label('Role')
                    ->badge(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordTitleAttribute('name')
                    ->recordSelectOptionsQuery(fn ($query) => $query->orderBy('name'))
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('role')
                            ->label('Company Role')
                            ->options(CompanyRoleEnum::class)
                            ->required()
                            ->default(CompanyRoleEnum::Employee->value),
                    ]),
            ]);
    }
}
