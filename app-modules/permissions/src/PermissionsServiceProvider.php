<?php

declare(strict_types=1);

namespace TresPontosTech\Permissions;

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
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
        ]);

        Gate::policy(Role::class, RolePolicy::class);

        Panel::configureUsing(function (Panel $panel) {
            if ($panel->getId() === 'admin') {
                $panel->plugin(new AdminRolePlugin);
            }
        });
    }

}
