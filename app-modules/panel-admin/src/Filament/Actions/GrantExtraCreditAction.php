<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Actions;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Billing\Core\Actions\Credit\GrantExtraCredit;
use TresPontosTech\Billing\Core\DTOs\GrantCreditDTO;
use TresPontosTech\Company\Models\Company;

class GrantExtraCreditAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'grant_extra_credit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::resources.credit_grants.actions.grant.label'))
            ->icon(Heroicon::OutlinedGift)
            ->modalHeading(__('panel-admin::resources.credit_grants.actions.grant.label'))
            ->form([
                TextInput::make('quantity')
                    ->label(__('panel-admin::resources.credit_grants.fields.quantity'))
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required(),

                Textarea::make('justification')
                    ->label(__('panel-admin::resources.credit_grants.fields.justification'))
                    ->required()
                    ->maxLength(1000),
            ]);
    }

    /**
     * Grant credits to a company (owner pool). Record is the Company.
     */
    public static function forCompany(): static
    {
        return static::make()
            ->modalDescription(__('panel-admin::resources.credit_grants.actions.grant.hint_company'))
            ->action(fn (array $data, Company $record) => self::grant($record, $data));
    }

    /**
     * Grant credits to a specific user from their own page, scoped to their primary company.
     * Record is the User.
     */
    public static function forUser(): static
    {
        return static::make()
            ->modalDescription(__('panel-admin::resources.credit_grants.actions.grant.hint_user'))
            ->action(function (array $data, User $record): void {
                /** @var Company|null $company */
                $company = $record->companies()->first();

                if ($company === null) {
                    Notification::make()
                        ->danger()
                        ->title(__('panel-admin::resources.credit_grants.notifications.user_without_company'))
                        ->send();

                    return;
                }

                self::grant($company, $data, $record);
            });
    }

    /**
     * Grant credits to an employee from the company's employees relation manager.
     * Record is the employee (User); the company is the relation owner.
     */
    public static function forEmployee(): static
    {
        return static::make()
            ->modalDescription(__('panel-admin::resources.credit_grants.actions.grant.hint_user'))
            ->action(function (array $data, User $record, RelationManager $livewire): void {
                $company = $livewire->getOwnerRecord();

                if (! $company instanceof Company) {
                    return;
                }

                self::grant($company, $data, $record);
            });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function grant(Company $company, array $data, ?User $targetUser = null): void
    {
        resolve(GrantExtraCredit::class)->handle(new GrantCreditDTO(
            adminUserId: (string) auth()->id(),
            company: $company,
            quantity: (int) $data['quantity'],
            justification: (string) $data['justification'],
            targetUser: $targetUser,
        ));

        Notification::make()
            ->success()
            ->title(__('panel-admin::resources.credit_grants.notifications.granted'))
            ->send();
    }
}
