<?php

namespace TresPontosTech\Admin\Filament\Resources\Companies\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\Plan;

class ContractualPlansRelationManager extends RelationManager
{
    protected static string $relationship = 'companyPlans';

    protected static ?string $title = 'Planos Contratuais';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('plan_id')
                    ->label('Plano da Empresa')
                    ->options(
                        Plan::query()->where('provider', BillingProviderEnum::Contractual)
                            ->where('type', BillableTypeEnum::Company)
                            ->where('active', true)
                            ->pluck('name', 'id')
                    )
                    ->required()
                    ->searchable(),

                TextInput::make('seats')
                    ->label('Cadeiras')
                    ->integer()
                    ->minValue(1)
                    ->required(),

                TextInput::make('monthly_appointments_per_employee')
                    ->label('Consultas/mês por funcionário')
                    ->integer()
                    ->minValue(1)
                    ->default(1)
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options(CompanyPlanStatusEnum::class)
                    ->default(CompanyPlanStatusEnum::Active)
                    ->required(),

                DatePicker::make('starts_at')
                    ->label('Início da vigência')
                    ->displayFormat('d/m/Y'),

                DatePicker::make('ends_at')
                    ->label('Fim da vigência')
                    ->displayFormat('d/m/Y'),

                Textarea::make('notes')
                    ->label('Observações')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('plan.name')
            ->columns([
                TextColumn::make('plan.name')
                    ->label('Plano')
                    ->searchable(),

                TextColumn::make('seats')
                    ->label('Cadeiras'),

                TextColumn::make('monthly_appointments_per_employee')
                    ->label('Consultas/mês'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (CompanyPlanStatusEnum $state): string|array => $state->getColor()),

                TextColumn::make('starts_at')
                    ->label('Início')
                    ->date('d/m/Y'),

                TextColumn::make('ends_at')
                    ->label('Fim')
                    ->date('d/m/Y'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
