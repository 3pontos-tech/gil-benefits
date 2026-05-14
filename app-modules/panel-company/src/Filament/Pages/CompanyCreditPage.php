<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Pages;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use TresPontosTech\Billing\Core\Actions\Credit\AllocateCreditToEmployee;
use TresPontosTech\Billing\Core\Actions\Credit\TransferCreditBetweenEmployees;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Jobs\DistributeCreditsEquallyJob;
use TresPontosTech\Billing\Core\Jobs\RevokeCreditsFromEmployeesJob;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\PanelCompany\Filament\Actions\PurchaseCreditsAction;
use TresPontosTech\PanelCompany\Filament\Widgets\CompanyCreditStatsWidget;

class CompanyCreditPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'credits';

    protected string $view = 'company-credits';

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::CreditCard;

    public static function getNavigationLabel(): string
    {
        return __('panel-company::resources.pages.credits.navigation_label');
    }

    public function getTitle(): string
    {
        return __('panel-company::resources.pages.credits.title');
    }

    protected function getHeaderWidgets(): array
    {
        return [CompanyCreditStatsWidget::class];
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->isAdmin()) {
            return true;
        }

        return auth()->user()->isCompanyOwner()
            && auth()->user()->ownedCompanies()->where('slug', filament()->getTenant()->slug)->exists();
    }

    public function table(Table $table): Table
    {
        $tenant = filament()->getTenant();
        $records = UserCredit::query()
            ->where('company_id', $tenant->getKey())
            ->where('owner_id', $tenant->owner->getKey())
            ->with(['holder', 'owner']);

        return $table
            ->query(
                $records
                    ->latest()
            )
            ->columns([
                TextColumn::make('holder.name')
                    ->label(__('panel-company::resources.pages.credits.columns.holder'))
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('panel-company::resources.pages.credits.columns.status'))
                    ->formatStateUsing(fn (UserCreditStatusEnum $state): string => $state->getLabel())
                    ->color(fn (UserCreditStatusEnum $state): array => $state->getColor()),
                TextColumn::make('owner.name')
                    ->label(__('panel-company::resources.pages.credits.columns.owner'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('transferred_at')
                    ->dateTime('d/m/Y H:i')
                    ->label(__('panel-company::resources.pages.credits.columns.transferred_at'))
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('panel-company::resources.pages.credits.columns.status'))
                    ->options(UserCreditStatusEnum::class),
            ])
            ->recordActions([
                Action::make('transfer')
                    ->label(__('panel-company::resources.actions.transfer_credit.label'))
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->visible(fn (UserCredit $record): bool => $record->status === UserCreditStatusEnum::Available)
                    ->schema(fn (): array => [
                        Select::make('employee_id')
                            ->label(__('panel-company::resources.actions.transfer_credit.employee'))
                            ->options($this->getActiveEmployeeOptions())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, UserCredit $record): void {
                        $employee = User::query()->findOrFail($data['employee_id']);
                        resolve(TransferCreditBetweenEmployees::class)->handle($record, $employee);
                    }),
            ])
            ->headerActions([
                PurchaseCreditsAction::make(),
                Action::make('revoke_all_credits')
                    ->label(__('panel-company::resources.actions.revoke_all_credits.label'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->disabled(fn (): bool => ! $this->hasDistributedCredits())
                    ->tooltip(fn (): ?string => $this->hasDistributedCredits()
                        ? null
                        : __('panel-company::resources.actions.revoke_all_credits.disabled_tooltip'))
                    ->action(function (): void {
                        dispatch(new RevokeCreditsFromEmployeesJob(filament()->getTenant()));

                        Notification::make()
                            ->title(__('panel-company::resources.actions.revoke_all_credits.queued_notification'))
                            ->info()
                            ->send();
                    }),
                Action::make('distribute_equally')
                    ->label(__('panel-company::resources.actions.distribute_equally.label'))
                    ->icon(Heroicon::OutlinedUsers)
                    ->requiresConfirmation()
                    ->disabled(fn (): bool => ! $this->canDistributeEqually())
                    ->tooltip(fn (): ?string => $this->canDistributeEqually()
                        ? null
                        : __('panel-company::resources.actions.distribute_equally.disabled_tooltip'))
                    ->action(function (): void {
                        dispatch(new DistributeCreditsEquallyJob(filament()->getTenant()));

                        Notification::make()
                            ->title(__('panel-company::resources.actions.distribute_equally.queued_notification'))
                            ->info()
                            ->send();
                    }),

                Action::make('distribute_manually')
                    ->label(__('panel-company::resources.actions.distribute_manually.label'))
                    ->icon(Heroicon::OutlinedArrowRight)
                    ->disabled(fn (): bool => ! $this->hasOwnerAvailableCredits())
                    ->tooltip(fn (): ?string => $this->hasOwnerAvailableCredits()
                        ? null
                        : __('panel-company::resources.actions.distribute_manually.disabled_tooltip'))
                    ->schema(fn (): array => [
                        TextEntry::make('notice')
                            ->label('')
                            ->state(new HtmlString(
                                '<p class="text-sm text-warning-600 dark:text-warning-400">' .
                                __('panel-company::resources.actions.distribute_manually.notice') .
                                '</p>'
                            )),
                        Select::make('employee_id')
                            ->label(__('panel-company::resources.actions.distribute_manually.employee'))
                            ->options($this->getActiveEmployeeOptions())
                            ->searchable()
                            ->required(),
                        TextInput::make('quantity')
                            ->label(__('panel-company::resources.actions.distribute_manually.quantity'))
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(fn () => $records->count())
                            ->required(),
                    ])
                    ->successNotificationTitle(__('panel-company::resources.actions.distribute_manually.success_notification'))
                    ->action(function (array $data, Action $action): void {
                        $employee = User::query()->findOrFail($data['employee_id']);
                        resolve(AllocateCreditToEmployee::class)->handle(
                            filament()->getTenant(),
                            $employee,
                            (int) $data['quantity'],
                        );
                        $action->success();
                    }),
            ]);
    }

    #[Computed]
    public function hasOwnerAvailableCredits(): bool
    {
        $company = filament()->getTenant();

        return UserCredit::query()
            ->where('company_id', $company->getKey())
            ->where('holder_id', $company->user_id)
            ->where('status', UserCreditStatusEnum::Available)
            ->exists();
    }

    #[Computed]
    public function hasDistributedCredits(): bool
    {
        $company = filament()->getTenant();

        return UserCredit::query()
            ->where('company_id', $company->getKey())
            ->where('holder_id', '!=', $company->user_id)
            ->where('status', UserCreditStatusEnum::Available)
            ->exists();
    }

    #[Computed]
    public function canDistributeEqually(): bool
    {
        $company = filament()->getTenant();

        $availableCredits = UserCredit::query()
            ->where('company_id', $company->getKey())
            ->where('holder_id', $company->user_id)
            ->where('status', UserCreditStatusEnum::Available)
            ->count();

        $employeeCount = $company->employees()->wherePivot('active', true)->where('users.id', '!=', $company->user_id)->count();

        return $employeeCount > 0 && $availableCredits >= $employeeCount;
    }

    /** @return array<string, string> */
    private function getActiveEmployeeOptions(): array
    {
        return filament()->getTenant()
            ->employees()
            ->wherePivot('active', true)
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }
}
