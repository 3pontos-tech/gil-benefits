---
name: modular
description: Create or modify Laravel modules using `internachi/modular`. Use when the user asks to create a module, add components to a module, scaffold module structure, or work on files in an `app-modules` directory.
argument-hint: <module-name> [component-type]
---

# Laravel Modular Development

You are helping with a Laravel application that uses `internachi/modular` for modular architecture. Modules live in `app-modules/` and follow Laravel package conventions.

## Module Structure

The structure of `app-modules` mimics a standard Laravel application, where what typically would be found in `app` is found in `src`:

```
app-modules/
  {module-name}/
    composer.json # PSR-4 autoload, Laravel provider discovery

    src/
      Providers/
      Models/
      Http/
    tests/
      Feature/
      Unit/
    routes/
      {module-name}-routes.php
    resources/
    database/
      migrations/
      factories/
      seeders/
```

## Creating a New Module

When asked to create a new module:

1. **Check if `internachi/modular` is installed:**
   ```bash
   composer show internachi/modular
   ```
   If not installed, install it first:
   ```bash
   composer require internachi/modular
   ```

2. **Check the modular vendor and namespace:**

    - Check for `config/app-modules.php`
    - If present, read two separate values: `modules_vendor` is the Composer package vendor
      (used in `composer.json` `name`, e.g. `3pontos-tech`) and `modules_namespace` is the PHP
      namespace root (e.g. `TresPontosTech`). They are not interchangeable.
    - If not, assume both are "modules" / "Modules"

3. **Create the module:**
   ```bash
   php artisan make:module {module-name} --no-interaction
   ```

4. **Register with Composer:**
   ```bash
   composer update {module-vendor}/{module-name}
   ```
5. **Sync modules**
   ```bash
   php artisan modules:sync
   ```

## Adding Components to a Module

Use the `--module` flag with Laravel's make commands:

| Component  | Command                                                                              |
|------------|--------------------------------------------------------------------------------------|
| Model      | `php artisan make:model {Name} --module={module} --no-interaction`                   |
| Controller | `php artisan make:controller {Name}Controller --module={module} --no-interaction`    |
| Migration  | `php artisan make:migration create_{table}_table --module={module} --no-interaction` |
| Factory    | `php artisan make:factory {Name}Factory --module={module} --no-interaction`          |
| Seeder     | `php artisan make:seeder {Name}Seeder --module={module} --no-interaction`            |
| Request    | `php artisan make:request {Name}Request --module={module} --no-interaction`          |
| Test       | `php artisan make:test {Name}Test --module={module} --no-interaction`                |
| Policy     | `php artisan make:policy {Name}Policy --module={module} --no-interaction`            |
| Event      | `php artisan make:event {Name} --module={module} --no-interaction`                   |
| Listener   | `php artisan make:listener {Name} --module={module} --no-interaction`                |
| Job        | `php artisan make:job {Name} --module={module} --no-interaction`                     |
| Middleware | `php artisan make:middleware {Name} --module={module} --no-interaction`              |
| Resource   | `php artisan make:resource {Name}Resource --module={module} --no-interaction`        |
| Rule       | `php artisan make:rule {Name} --module={module} --no-interaction`                    |
| Observer   | `php artisan make:observer {Name}Observer --module={module} --no-interaction`        |

## Module Conventions

### Namespacing

- Default namespace: `{ModulesNamespace}\{ModuleName}\`, where `{ModulesNamespace}` is
  `modules_namespace` from `config/app-modules.php`, not the Composer vendor
- Example: `TresPontosTech\Billing\Models\Invoice` (package name `3pontos-tech/billing`)

### composer.json Format

```json
{
    "name": "{module-vendor}/{module-name}",
    "require": {},
    "autoload": {
        "psr-4": {
            "{ModulesNamespace}\\{ModuleName}\\": "src/",
            "{ModulesNamespace}\\{ModuleName}\\Tests\\": "tests/",
            "{ModulesNamespace}\\{ModuleName}\\Database\\Factories\\": "database/factories/",
            "{ModulesNamespace}\\{ModuleName}\\Database\\Seeders\\": "database/seeders/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "{ModulesNamespace}\\{ModuleName}\\{ModuleName}ServiceProvider"
            ]
        }
    }
}
```

The four PSR-4 entries are what `make:module` generates. Without the `Tests`, `Database\Factories`
and `Database\Seeders` mappings, Composer looks for factories and seeders under `src/` and they
fail with class-not-found.

### Routes

- By convention, module routes are in `routes/{module-name}-routes.php`
- By convention, module route names are prefixed with the module name (eg. `billing::dashboards.index`)
- If necessary, break into separate files as needed (eg. `routes/{module-name}-api.php`)
- Routes are auto-discovered—no need to register them
- Module route files are required directly, outside the `web` and `api` groups of
  `routes/web.php` and `routes/api.php`. Browser routes must apply the `web` middleware group
  explicitly (session, CSRF) plus any auth middleware; API routes apply their own middleware
  the same way

### Migrations

- Place in `database/migrations/`
- Auto-discovered by Laravel's migrator
- Run with `php artisan migrate`

### Factories

- Place in `database/factories/`
- Auto-loaded for `factory()` calls
- Namespace: `{ModulesNamespace}\{ModuleName}\Database\Factories`

### Tests

- Place in `tests/Feature/` and `tests/Unit/`
- Run module tests: `php artisan test app-modules/{module-name}/tests`
- All module tests can be run using the `Modules` testsuite configuration that is auto-generated by the `modules:sync` command

### Cross-Module Dependencies

- Import models/services from other modules directly
- Example: `use TresPontosTech\Billing\Models\Invoice;`
- Keep dependencies minimal and unidirectional when possible

## Available Commands

```bash

# List all modules

php artisan modules:list

# Sync phpunit.xml and IDE configs

php artisan modules:sync

# Cache module configs

php artisan modules:cache

# Clear module cache

php artisan modules:clear

# Run module seeders

php artisan db:seed --module={module-name}
```

## Best Practices

1. **One domain per module** - Group related functionality together
2. **Minimal cross-dependencies** - Modules should be loosely coupled
3. **Follow Laravel conventions** - Use standard directory structure within modules (treat `src` like `app`)

## When Processing Arguments

If `$ARGUMENTS` contains:

- Just a module name (e.g., "billing"): Create the module or list what can be added
- Module + component (e.g., "billing model Invoice"): Create that specific component
- "list": Show existing modules with `php artisan modules:list`