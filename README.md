# Gil Benefits

Multi-tenant financial consultancy management platform. Manages consultants, appointments, billing subscriptions, and company hierarchies through three distinct Filament-powered panels.

---

## Core Concepts

- **Multi-tenancy** — companies act as tenants; users belong to one or more companies
- **Four panels** — Admin, App (user-facing), Consultants and Company each expose different capabilities via Filament
- **Modular domain architecture** — business logic is split into self-contained app-modules with their own models, migrations, tests, and service providers
- **Action pattern** — complex operations are encapsulated in single-responsibility Action classes
- **Stripe subscriptions** — tiered billing plans (Gold, Black, Enterprise) with portal management
- **Google Calendar sync** — appointments are synchronised to/from Google Calendar via a service account

---

## Modules

| Module | Responsibility |
|---|---|
| `appointments` | Scheduling, state machine lifecycle, calendar sync |
| `billing` | Stripe subscriptions, plans, pricing tiers, portals |
| `company` | Company/organisation management and hierarchy |
| `consultants` | Consultant profiles and assignment |
| `permissions` | RBAC via Spatie Permission |
| `tenant` | Multi-tenancy scaffolding |
| `user` | User profiles and authentication |
| `panel-admin` | Filament admin panel |
| `panel-app` | Filament user-facing panel |
| `panel-company` | Filament company management panel |
| `integration-google-calendar` | Google Calendar API integration |

---

## Architecture Overview

```
app/                        # Shared providers, middleware, base models
app-modules/
  <module>/
    src/                    # Domain logic (Actions, DTOs, Enums, Models, Repositories)
    database/               # Module-specific migrations and seeders
    tests/                  # Module-scoped Pest tests
    composer.json           # Module namespace (TresPontosTech\<Module>)
config/                     # Application configuration
database/                   # Core migrations and seeders
routes/                     # Route definitions
tests/                      # Feature, Unit, and E2E test suites
```

Panels are built with **Filament v5** and organised into Clusters and Resources per panel. Static analysis runs at **PHPStan level max** via Larastan.

---

## Quick Start

```bash
# First-time setup
cp .env.example .env
composer install && npm install
php artisan key:generate

# Database
make migrate-fresh          # fresh migrations + seed

# Dev server (serves, queue, logs, vite — all at once)
composer dev

# Stripe webhooks (separate terminal)
make stripe-listen
```

---

## Required Keys

| Key | Description |
|---|---|
| `STRIPE_KEY` | Stripe publishable key |
| `STRIPE_SECRET` | Stripe secret key |
| `STRIPE_WEBHOOK_SECRET` | Stripe webhook signing secret |
| `FLAMMA_STRIPE_*_PRODUCT_ID` / `*_PRICE_ID` | Product & price IDs for each plan tier (Gold, Platinum, Black, Enterprise) |
| `FLAMMA_STRIPE_*_PORTAL_ID` | Billing portal IDs (enterprise and user) |
| `RESEND_API_KEY` | Resend transactional email |
| `RESEND_WEBHOOK_SECRET` | Resend webhook verification |
| `GOOGLE_SERVICE_ACCOUNT_CREDENTIALS` | JSON credentials for Calendar sync |

See `.env.example` for the full list.

---

## Development

### Module Structure

```
app-modules/<module>/src/
  Actions/        # Single-responsibility business operations
  DTOs/           # Data transfer objects
  Enums/          # Domain enumerations
  Models/         # Eloquent models
  Repositories/   # (where applicable) data access abstraction
  Filament/       # Resources, Pages, Clusters (panel modules only)
  Providers/      # Module service provider
```

### Conventions

| Area | Convention |
|---|---|
| Namespace | `TresPontosTech\<ModuleName>` |
| Code style | Laravel preset via **Pint** (`pint.json`) |
| Static analysis | **PHPStan** level max via Larastan (`phpstan.neon`) |
| Refactoring | **Rector** with Laravel + quality sets (`rector.php`) |
| Testing | **Pest v4** — Unit, Feature, and E2E suites |
| Locale | `pt_BR` |

### Key Commands

```bash
make pint           # Fix code style
make phpstan        # Run static analysis
make rector         # Apply automated refactors
make refacto        # rector + pint
make test           # Run all tests (parallel, no browser)
make test-pest      # Run full test suite
make check          # rector + pint + pest (CI equivalent)

make stripe-fresh   # Fresh DB + essentials seeder + Stripe sync
make stripe-listen  # Forward Stripe webhooks to localhost:8000
```

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4 |
| Framework | Laravel 12 |
| Admin UI | Filament 5 |
| Frontend | Vite 6, Tailwind CSS 4, Axios |
| Billing | Stripe (Laravel Cashier) |
| Media | Spatie Laravel MediaLibrary |
| Mail | Resend |
| Queue / Cache / Session | Database driver (default) |
