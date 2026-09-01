<?php

namespace TresPontosTech\PanelAdmin\Filament\Resources\Permissions\Actions;

use App\Models\Users\User;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

class AssignRoleAction extends Action
{
    protected Company|Closure|null $company = null;

    /**
     * When a company is set, the action assigns a per-tenant role in the
     * company_employees pivot. Otherwise it assigns a global Spatie role.
     */
    public function company(Company|Closure $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function resolveCompany(): ?Company
    {
        return $this->company instanceof Closure
            ? ($this->company)()
            : $this->company;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->label(__('panel-admin::resources.permissions.assign_role'));
        $this->icon(Heroicon::OutlinedShieldCheck);
        $this->visible(fn (): bool => auth()->user()->hasRole(Roles::SuperAdmin));
        $this->schema(fn (): array => $this->roleSchema());
        $this->action($this->roleAction(...));
    }

    public static function getDefaultName(): ?string
    {
        return 'assign-role-action';
    }

    /**
     * Company scope exposes the per-tenant roles; global scope exposes the
     * identity roles. Company roles (owner/manager/employee) are never assigned
     * globally, and identity roles are never written to a company pivot.
     *
     * @return array<int, Roles>
     */
    private function availableRoles(): array
    {
        return $this->resolveCompany() instanceof Company
            ? [Roles::CompanyManager, Roles::Employee]
            : [
                Roles::SuperAdmin,
                Roles::Admin,
                Roles::Consultant,
                Roles::User,
                Roles::Financial,
                Roles::CustomerSuccess,
            ];
    }

    /**
     * @return array<Component>
     */
    private function roleSchema(): array
    {
        $options = [];

        foreach ($this->availableRoles() as $role) {
            $options[$role->value] = $role->getLabel();
        }

        return [
            Select::make('role')
                ->options($options)
                ->required(),
        ];
    }

    private function roleAction(User $record): Notification
    {
        $selected = $this->data['role'];
        $role = $selected instanceof Roles ? $selected : Roles::from($selected);
        $company = $this->resolveCompany();

        // Per-tenant assignment: the role lives in the company_employees pivot.
        if ($company instanceof Company) {
            $company->employees()->syncWithoutDetaching([
                $record->getKey() => ['role' => $role->value],
            ]);

            return Notification::make()
                ->success()
                ->body(sprintf(__('panel-admin::resources.permissions.user_assigned_to_role'), $role->getLabel()))
                ->send();
        }

        // Global assignment.
        if ($record->hasRole($role)) {
            return Notification::make()
                ->info()
                ->body(__('panel-admin::resources.permissions.user_already_has_role'))
                ->send();
        }

        $record->assignRole($role->value);

        return Notification::make()
            ->success()
            ->body(sprintf(__('panel-admin::resources.permissions.user_assigned_to_role'), $role->getLabel()))
            ->send();
    }
}
