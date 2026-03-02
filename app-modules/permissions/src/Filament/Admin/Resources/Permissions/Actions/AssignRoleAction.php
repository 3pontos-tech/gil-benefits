<?php

namespace TresPontosTech\Permissions\Filament\Admin\Resources\Permissions\Actions;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Permissions\Roles;

class AssignRoleAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->label('Assign Role');
        $this->icon(Heroicon::OutlinedShieldCheck);
        $this->visible(fn () => auth()->user()->hasRole(Roles::SuperAdmin));
        $this->schema($this->roleSchema());
        $this->action($this->roleAction(...));
    }

    public static function getDefaultName(): ?string
    {
        return 'assign-role-action';
    }

    private function roleSchema(): array
    {
        return [
            Select::make('role')
                ->options(Roles::class)
                ->enum(Roles::class),
        ];
    }

    private function roleAction(User $record): Notification
    {
        /** @var Roles $role */
        $role = $this->data['role'];

        if ($record->hasRole($role)) {
            return Notification::make()
                ->info()
                ->body('User already has this role')
                ->send();
        }

        $record->assignRole($role->value);

        return Notification::make()
            ->success()
            ->body(sprintf('User has been assigned to %s role', $role->name))
            ->send();
    }
}
