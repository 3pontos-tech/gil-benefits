<?php

declare(strict_types=1);

namespace TresPontosTech\Permissions;

use App\Models\Users\User;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Tiptap\Nodes\Details;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\Commands\SyncPermissions\SyncPermissionsCommand;

class PermissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/permissions.php', 'permission');
        $this->commands(SyncPermissionsCommand::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'permissions');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'permissions');

        Relation::morphMap([
            'roles' => Role::class,
            'permissions' => Permission::class,
            'users' => User::class,
            'consultants' => Consultant::class,
            'appointments' => Appointment::class,
            'company' => Company::class,
            'details' => Details::class,
        ]);

        Gate::policy(Role::class, RolePolicy::class);

        Panel::configureUsing(function (Panel $panel) {
            if ($panel->getId() === 'admin') {
                $panel->plugin(new AdminRolePlugin);
            }
        });
    }
}
