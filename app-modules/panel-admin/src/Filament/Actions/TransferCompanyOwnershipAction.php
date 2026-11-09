<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Actions;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Company\Actions\TransferCompanyOwnership;
use TresPontosTech\Company\Enums\PreviousOwnerOutcome;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\CompanyResource;

/**
 * Header action of the company edit page. The owner select of the form is
 * create-only; after that the responsible only changes through the domain
 * action, so the pivot and the credits move together. Hidden on the default
 * bucket: its responsible is the platform, not a client.
 *
 * The fate of the previous owner has no default. Keeping them as manager
 * grants company-panel access, and the admin panel has no way to take it back
 * afterwards, so the admin decides it here.
 */
class TransferCompanyOwnershipAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'transfer_ownership';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::resources.companies.actions.transfer_ownership.label'))
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('warning')
            ->modalHeading(__('panel-admin::resources.companies.actions.transfer_ownership.label'))
            ->modalDescription(__('panel-admin::resources.companies.actions.transfer_ownership.description'))
            ->requiresConfirmation()
            ->hidden(fn (Company $record): bool => $record->isDefaultBucket())
            ->schema(fn (Company $record): array => [
                Select::make('user_id')
                    ->label(__('panel-admin::resources.companies.actions.transfer_ownership.new_owner'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => User::query()
                        ->whereKeyNot($record->user_id)
                        ->where(fn (Builder $query): Builder => $query
                            ->whereLike('name', "%{$search}%")
                            ->orWhereLike('email', "%{$search}%"))
                        ->orderBy('name')
                        ->limit(20)
                        ->pluck('name', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn (string $value): ?string => User::query()->find($value)?->name)
                    ->required(),
                Radio::make('previous_owner_outcome')
                    ->label(__('panel-admin::resources.companies.actions.transfer_ownership.previous_owner'))
                    ->options([
                        PreviousOwnerOutcome::KeepAsManager->value => __('panel-admin::resources.companies.actions.transfer_ownership.keep_as_manager'),
                        PreviousOwnerOutcome::RevokeAccess->value => __('panel-admin::resources.companies.actions.transfer_ownership.revoke_access'),
                    ])
                    ->descriptions([
                        PreviousOwnerOutcome::KeepAsManager->value => __('panel-admin::resources.companies.actions.transfer_ownership.keep_as_manager_hint'),
                        PreviousOwnerOutcome::RevokeAccess->value => __('panel-admin::resources.companies.actions.transfer_ownership.revoke_access_hint'),
                    ])
                    ->required(),
            ])
            ->successRedirectUrl(fn (Company $record): string => CompanyResource::getUrl('edit', ['record' => $record]))
            ->action(function (array $data, Company $record, TransferCompanyOwnership $transfer): void {
                /** @var User $newOwner */
                $newOwner = User::query()->findOrFail($data['user_id']);

                $transfer->handle($record, $newOwner, PreviousOwnerOutcome::from($data['previous_owner_outcome']));

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::resources.companies.actions.transfer_ownership.success'))
                    ->send();

                $this->success();
            });
    }
}
