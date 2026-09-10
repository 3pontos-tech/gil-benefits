<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use TresPontosTech\Billing\Core\Enums\CompanyPlanKindEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;

class VoucherBatchForm
{
    public const MAX_QUANTITY = 5000;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_plan_id')
                    ->label(__('panel-admin::resources.voucher_batches.form.program'))
                    ->helperText(__('panel-admin::resources.voucher_batches.form.program_hint'))
                    ->options(fn (): array => self::programOptions())
                    ->required()
                    ->searchable(),

                TextInput::make('name')
                    ->label(__('panel-admin::resources.voucher_batches.form.name'))
                    ->maxLength(255)
                    ->required(),

                TextInput::make('quantity')
                    ->label(__('panel-admin::resources.voucher_batches.form.quantity'))
                    ->helperText(__('panel-admin::resources.voucher_batches.form.quantity_hint'))
                    ->integer()
                    ->minValue(1)
                    ->maxValue(self::MAX_QUANTITY)
                    ->required(),

                DatePicker::make('expires_at')
                    ->label(__('panel-admin::resources.voucher_batches.form.expires_at'))
                    ->helperText(__('panel-admin::resources.voucher_batches.form.expires_at_hint'))
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('today'),

                Textarea::make('notes')
                    ->label(__('panel-admin::resources.voucher_batches.form.notes'))
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function programOptions(): array
    {
        return CompanyPlan::query()
            ->with('company')
            ->where('kind', CompanyPlanKindEnum::CreditsOnly)
            ->activeOn()
            ->get()
            ->mapWithKeys(fn (CompanyPlan $plan): array => [
                $plan->getKey() => sprintf('%s — %s', $plan->company?->name, __('panel-admin::resources.voucher_batches.form.seats_summary', ['seats' => $plan->seats])),
            ])
            ->all();
    }
}
