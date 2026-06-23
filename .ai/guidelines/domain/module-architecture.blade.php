# Module Architecture

This monorepo uses `internachi/modular`. Each module lives under
`app-modules/{kebab-case}/`, is published as `3pontos-tech/{slug}`, and uses the
namespace `TresPontosTech\{PascalCase}\`.

> The namespace does **not** always match the directory name — always confirm in the
> module's `composer.json`. Presentation modules in particular follow the
> `TresPontosTech\Panel{Name}` standard (see the table below).

## Module types

| Type             | Modules                                                                  | Contains                                       |
| ---------------- | ------------------------------------------------------------------------ | ---------------------------------------------- |
| **Domain**       | `appointments`, `billing`, `company`, `consultants`, `permissions`, `support`, `tenant`, `user` | Business logic: Models, Actions, DTOs, Enums   |
| **Integration**  | `integration-google-calendar`, `integration-highlevel`                   | External APIs: Actions, Requests, Responses, Jobs, Console |
| **Presentation** | `panel-admin`, `panel-app`, `panel-company`, `panel-consultant`          | UI: Filament Resources/Pages/Widgets, Livewire |

Presentation modules own UI concerns only. Domain logic belongs in domain modules —
see the `presentation/core` guideline.

## Namespace standard for `panel-*` modules

Presentation modules use `TresPontosTech\Panel{Name}` (PascalCase of the directory):

| Module             | Namespace                       |
| ------------------ | ------------------------------- |
| `panel-admin`      | `TresPontosTech\PanelAdmin`     |
| `panel-app`        | `TresPontosTech\PanelApp`       |
| `panel-company`    | `TresPontosTech\PanelCompany`   |
| `panel-consultant` | `TresPontosTech\PanelConsultant`|

This matches the provider class names already in use (`PanelAdminServiceProvider`, …).
Some panels are still being aligned to this standard — trust the `composer.json` for
the current value, and target this standard when creating or renaming a panel.

## Canonical structure

```
app-modules/{module}/
├── composer.json
├── phpstan.neon
├── config/{module}.php                       (optional)
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── lang/{en,pt_BR}/                          (optional)
├── routes/{topic}-routes.php                 (optional, auto-discovered)
├── resources/views/                          (optional, presentation only)
├── resources/boost/guidelines/              (optional, module-scoped guidelines)
├── src/
│   ├── Providers/{ModuleName}ServiceProvider.php
│   ├── Actions/
│   ├── Models/
│   ├── DTOs/
│   ├── Enums/
│   ├── Exceptions/
│   ├── Policies/
│   └── ...
└── tests/
    ├── Feature/
    └── Unit/
```

## Sub-namespace strategies

**Flat layers** — most domain modules (`appointments`, `company`, `user`, `support`):
`src/Actions/`, `src/Models/`, `src/DTOs/`, `src/Enums/`.

**Sub-domain grouping** — complex modules:
- `billing` → `Core/`, `Stripe/`, `Barte/` (each with its own Models/Actions/DTOs).
- presentation modules → `Filament/` with `Resources/`, `Pages/`, `Widgets/`, `Clusters/`.

## ServiceProvider

Place the ServiceProvider at `src/Providers/{ModuleName}ServiceProvider.php` (the
dominant convention here). A few legacy modules keep it at the `src/` root
(`billing`, `permissions`, `integration-highlevel`) — follow `src/Providers/` for new
modules. Register the provider in the module's `composer.json` under
`extra.laravel.providers`.

@verbatim
<code-snippet name="Module ServiceProvider" lang="php">
namespace TresPontosTech\{ModuleName}\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class {ModuleName}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/{module}.php', '{module}');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', '{module}');

        Relation::morphMap([
            'some_class' => SomeClass::class,
        ]);
    }
}
</code-snippet>
@endverbatim

Check a sibling module's ServiceProvider (e.g. `appointments`, `company`) for the full
pattern before adding `loadViewsFrom()`, `Event::listen()`, `Gate::policy()`, etc.

## Module composer.json

@verbatim
<code-snippet name="Module composer.json" lang="json">
{
    "name": "3pontos-tech/{module-slug}",
    "autoload": {
        "psr-4": {
            "TresPontosTech\\{ModuleName}\\": "src/",
            "TresPontosTech\\{ModuleName}\\Database\\Factories\\": "database/factories/",
            "TresPontosTech\\{ModuleName}\\Database\\Seeders\\": "database/seeders/"
        }
    }
}
</code-snippet>
@endverbatim

## Dependency rules

- **Domain** modules never import from Presentation or Integration.
- **Integration** modules may depend on Domain (e.g. resolving a `User` or `Company`).
- **Presentation** imports from Domain and Integration, never the reverse.
- The canonical `User` model lives in the app core (`App\Models\Users\User`), not in a
  module; the `user` module holds related domain pieces (anamnese, events, jobs).
