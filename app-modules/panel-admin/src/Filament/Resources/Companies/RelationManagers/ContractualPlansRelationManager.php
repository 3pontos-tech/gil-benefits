<?php

namespace TresPontosTech\Admin\Filament\Resources\Companies\RelationManagers;

use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;

class ContractualPlansRelationManager extends RelationManager
{
    protected static string $relationship = 'companyPlans';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('panel-admin::resources.companies.relation_managers.contractual_plans.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('plan_id')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.form.plan'))
                    ->options(
                        Plan::query()->where('provider', BillingProviderEnum::Contractual)
                            ->where('type', BillableTypeEnum::Company)
                            ->where('active', true)
                            ->pluck('name', 'id')
                    )
                    ->required()
                    ->searchable(),

                TextInput::make('seats')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.form.seats'))
                    ->integer()
                    ->minValue(1)
                    ->required(),

                TextInput::make('monthly_appointments_per_employee')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.form.monthly_appointments'))
                    ->integer()
                    ->minValue(1)
                    ->default(1)
                    ->required(),

                Select::make('status')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.form.status'))
                    ->options(CompanyPlanStatusEnum::class)
                    ->default(CompanyPlanStatusEnum::Active)
                    ->required()
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            $statusValue = $value instanceof CompanyPlanStatusEnum ? $value->value : $value;

                            if ($statusValue !== CompanyPlanStatusEnum::Active->value) {
                                return;
                            }

                            $companyId = $this->getOwnerRecord()->getKey();
                            $recordId = $this->getMountedAction()?->getRecord()?->getKey();
                            $startsAt = $get('starts_at') ?? now()->toDateString();
                            $endsAt = $get('ends_at') ?? '9999-12-31';

                            $overlap = CompanyPlan::query()
                                ->where('company_id', $companyId)
                                ->where('status', CompanyPlanStatusEnum::Active)
                                ->when($recordId, fn ($q) => $q->where('id', '!=', $recordId))
                                ->where(fn ($q) => $q
                                    ->whereNull('ends_at')
                                    ->orWhere('ends_at', '>=', $startsAt)
                                )
                                ->where(fn ($q) => $q
                                    ->whereNull('starts_at')
                                    ->orWhere('starts_at', '<=', $endsAt)
                                )
                                ->exists();

                            if ($overlap) {
                                $fail(__('panel-admin::resources.companies.relation_managers.contractual_plans.form.overlap_error'));
                            }
                        },
                    ]),

                DatePicker::make('starts_at')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.form.starts_at'))
                    ->displayFormat('d/m/Y'),

                DatePicker::make('ends_at')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.form.ends_at'))
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual(fn (Get $get): ?string => $get('starts_at')),

                Textarea::make('notes')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.form.notes'))
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('plan.name')
            ->columns([
                TextColumn::make('plan.name')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.table.plan'))
                    ->searchable(),

                TextColumn::make('seats')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.table.seats')),

                TextColumn::make('monthly_appointments_per_employee')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.table.monthly_appointments')),

                TextColumn::make('status')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.table.status'))
                    ->badge()
                    ->color(fn (CompanyPlanStatusEnum $state): string|array => $state->getColor()),

                TextColumn::make('starts_at')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.table.starts_at'))
                    ->date('d/m/Y'),

                TextColumn::make('ends_at')
                    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.table.ends_at'))
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
