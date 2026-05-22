<?php

namespace TresPontosTech\PanelCompany\Filament\Pages\Tenancy;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\EditTenantProfile as BaseEditTenantProfile;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Str;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Actions\CreateAndAttachAction;
use TresPontosTech\PanelCompany\Filament\Actions\TenantSeatsCounterAction;
use TresPontosTech\PanelCompany\Filament\Actions\TenantSecretKeyRotationPanelAction;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Concerns\ChecksImportCompletion;
use TresPontosTech\User\Filament\Actions\ImportUsersAction;

class EditTenantProfile extends BaseEditTenantProfile implements HasTable
{
    use ChecksImportCompletion;
    use InteractsWithTable;

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isCompanyOwner()) {
            return true;
        }

        return $user->isCompanyManager();
    }

    public static function getLabel(): string
    {
        return __('panel-company::resources.pages.edit_tenant.label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('panel-company::resources.pages.edit_tenant.form_name'))
                    ->maxLength(255)
                    ->live(onBlur: true, debounce: 500)
                    ->afterStateUpdated(function (Set $set, $state): void {
                        $set('slug', Str::slug($state));
                    })
                    ->readOnly(),
                TextInput::make('tax_id')
                    ->label(__('panel-company::resources.pages.edit_tenant.form_tax_id'))
                    ->mask('99.999.999/9999-99')
                    ->readOnly(),
                TextInput::make('integration_access_key')
                    ->label(__('panel-company::resources.pages.edit_tenant.form_integration_access_key'))
                    ->readOnly()
                    ->live(),
            ])
            ->columns(3);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->hidden();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(filament()->getTenant()->employees()->getQuery())
            ->heading(__('panel-company::resources.pages.edit_tenant.members_heading'))
            ->headerActions([
                TenantSeatsCounterAction::make(),
                CreateAndAttachAction::make('Invite Member')
                    ->label(__('panel-company::resources.pages.edit_tenant.invite_member'))
                    ->model(User::class),
                ImportUsersAction::make()
                    ->company(fn (): ?Model => filament()->getTenant()),
            ])
            ->recordActions([
                Action::make('toggle-manager')
                    ->label(fn (User $record): string => $record->hasRole(Roles::CompanyManager)
                        ? __('panel-company::resources.pages.edit_tenant.remove_manager')
                        : __('panel-company::resources.pages.edit_tenant.make_manager'))
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->color(fn (User $record): string => $record->hasRole(Roles::CompanyManager) ? 'danger' : 'warning')
                    ->requiresConfirmation()
                    ->visible(function (User $record): bool {
                        /** @var User $actingUser */
                        $actingUser = auth()->user();
                        /** @var Company $company */
                        $company = filament()->getTenant();

                        if ($record->getKey() === $actingUser->getKey()) {
                            return false;
                        }

                        if ($record->getKey() === $company->user_id) {
                            return false;
                        }

                        return ! ($actingUser->isCompanyManager() && $record->hasRole(Roles::CompanyManager));
                    })
                    ->action(function (User $record): void {
                        if ($record->hasRole(Roles::CompanyManager)) {
                            $record->removeRole(Roles::CompanyManager);

                            Notification::make()
                                ->title(__('panel-company::resources.pages.edit_tenant.remove_manager_notification', ['name' => $record->name]))
                                ->success()
                                ->send();
                        } else {
                            $record->assignRole(Roles::CompanyManager);

                            Notification::make()
                                ->title(__('panel-company::resources.pages.edit_tenant.make_manager_notification', ['name' => $record->name]))
                                ->success()
                                ->send();
                        }
                    }),
                Action::make('toggle-active')
                    ->label(fn (User $record): string => $record->active
                        ? __('panel-company::resources.pages.edit_tenant.deactivate')
                        : __('panel-company::resources.pages.edit_tenant.activate'))
                    ->visible(function (User $record): bool {
                        /** @var Company $company */
                        $company = filament()->getTenant();

                        return $record->getKey() !== $company->user_id;
                    })
                    ->action(function (User $record): void {
                        /** @var Company $company */
                        $company = filament()->getTenant();

                        $company->employees()->updateExistingPivot($record, [
                            'active' => ! $record->active,
                            'role' => $record->role,
                        ]);
                    }),
                DetachAction::make()
                    ->visible(function (User $record): bool {
                        /** @var Company $company */
                        $company = filament()->getTenant();

                        return $record->getKey() !== $company->user_id;
                    })
                    ->action(fn ($record) => filament()->getTenant()->employees()->detach($record)),
            ])
            ->columns([
                TextColumn::make('active')
                    ->badge()
                    ->label(__('panel-company::resources.pages.edit_tenant.status'))
                    ->formatStateUsing(fn ($state): string => $state
                        ? __('panel-company::resources.pages.edit_tenant.active')
                        : __('panel-company::resources.pages.edit_tenant.inactive'))
                    ->color(fn ($state): string => $state ? 'success' : 'danger')
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('panel-company::resources.pages.edit_tenant.member_name'))
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label(__('panel-company::resources.pages.edit_tenant.member_role'))
                    ->color(fn ($state) => Roles::from($state)->getColor())
                    ->formatStateUsing(fn ($state) => Roles::from($state)->getLabel())
                    ->badge(),
            ]);
    }

    protected function getActions(): array
    {
        return [
            TenantSecretKeyRotationPanelAction::make(),
            Action::make('company_logo')
                ->label(__('panel-company::resources.actions.logo.label'))
                ->icon(Heroicon::OutlinedCamera)
                ->schema([
                    SpatieMediaLibraryFileUpload::make('company_logo')
                        ->label(__('panel-company::resources.actions.logo.label'))
                        ->model(filament()->getTenant())
                        ->collection('company_logo')
                        ->maxFiles(1)
                        ->acceptedFileTypes([
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ])
                        ->required(),
                ])->after(function (): Redirector|RedirectResponse {
                    Notification::make('success')
                        ->title(__('panel-company::resources.actions.logo.notification'))
                        ->success()
                        ->send();

                    return redirect(request()->header('Referer'));
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                EmbeddedTable::make(),
            ]);
    }
}
