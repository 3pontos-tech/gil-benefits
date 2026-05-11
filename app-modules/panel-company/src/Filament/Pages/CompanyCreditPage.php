<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Pages;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Attributes\Computed;
use TresPontosTech\Billing\Core\Actions\AllocateCreditToEmployee;
use TresPontosTech\Billing\Core\Actions\TransferCreditBetweenEmployees;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;

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

    public static function canAccess(): bool
    {
        if (auth()->user()->isAdmin()) {
            return true;
        }

        return auth()->user()->isCompanyOwner()
            && auth()->user()->ownedCompanies()->where('slug', filament()->getTenant()->slug)->exists();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('distribute_equally')
                ->label(__('panel-company::resources.actions.distribute_equally.label'))
                ->icon(Heroicon::OutlinedUsers)
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->canDistributeEqually())
                ->action(function (): void {
                    resolve(AllocateCreditToEmployee::class)->handleEqually(filament()->getTenant());
                }),

            Action::make('distribute_manually')
                ->label(__('panel-company::resources.actions.distribute_manually.label'))
                ->icon(Heroicon::OutlinedArrowRight)
                ->form(fn (): array => [
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
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $employee = User::query()->findOrFail($data['employee_id']);
                    resolve(AllocateCreditToEmployee::class)->handle(
                        filament()->getTenant(),
                        $employee,
                        (int) $data['quantity'],
                    );
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                UserCredit::query()
                    ->where('company_id', filament()->getTenant()?->getKey())
                    ->with(['holder', 'owner'])
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
                    ->form(fn (): array => [
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
            ]);
    }

    #[Computed]
    public function canDistributeEqually(): bool
    {
        $company = filament()->getTenant();

        $availableCount = UserCredit::query()
            ->where('company_id', $company->getKey())
            ->where('holder_id', $company->user_id)
            ->where('status', UserCreditStatusEnum::Available)
            ->count();

        $employeeCount = $company->employees()->wherePivot('active', true)->count();

        return $employeeCount > 0 && $availableCount >= $employeeCount;
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
