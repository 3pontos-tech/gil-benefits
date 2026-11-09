<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Actions;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Company\Actions\TransferCompanyOwnership;
use TresPontosTech\Company\Models\Company;

/**
 * Header action of the company edit page. The owner select of the form is
 * create-only; after that the responsible only changes through the domain
 * action, so the pivot and the credits move together.
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
            ])
            ->action(function (array $data, Company $record, TransferCompanyOwnership $transfer): void {
                /** @var User $newOwner */
                $newOwner = User::query()->findOrFail($data['user_id']);

                $transfer->handle($record, $newOwner);

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::resources.companies.actions.transfer_ownership.success'))
                    ->send();
            });
    }
}
