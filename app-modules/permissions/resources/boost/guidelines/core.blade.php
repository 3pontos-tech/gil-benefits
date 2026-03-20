## Permissions - RBAC system built on Spatie/laravel-permission with custom table names and sync commands. - Namespace:
`He4rt\Permissions\`. No translation namespace (uses config-driven labels). ### Models - **Permission** (extends
`Spatie\Permission\Models\Permission`) — Custom permission model using `rbac_permissions` table. - Extra fields:
`resource`, `action`, `resource_group`. - Computed attributes: `formatted_name`, `resource_model`. - **Role** (extends
`Spatie\Permission\Models\Role`) — Custom role model using `rbac_roles` table. - Uses UUID primary key. Relationships:
`permissions()` (inherited from Spatie). ### Enums - **PermissionsEnum**: View, ViewAny, Create, Update, Delete,
Restore, ForceDelete. - Method: `buildPermissionFor(string $classPath)` — generates permission name strings like
`view_user`. - **Roles**: SuperAdmin, User — Used by every policy in the application for authorization checks. ###
Commands - `sync:permissions` — Syncs permissions from morph map, syncs roles from `Roles` enum, assigns all permissions
to SuperAdmin. Reads from `config/rbac.php` files. ### Cross-Module Dependencies - Depends on: `users` (User model for
guard resolution). - Used by: **nearly every module** — all policies check `Roles::SuperAdmin`, all resources reference
permissions. ### Conventions - Custom Spatie permission tables: `rbac_permissions`, `rbac_roles`,
`rbac_model_has_permissions`, `rbac_model_has_roles`, `rbac_role_has_permissions`. - Config files: `permissions.php`
(Spatie override), `rbac.php` (module RBAC definitions). - Morph map entries: `'roles' => Role::class`, `'permissions'
=> Permission::class`. - RolePolicy registered via `Gate::policy()` — SuperAdmin-only access.
