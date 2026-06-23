# Gil Benefits — Project Context

Gil Benefits is a **B2B2C financial-consulting platform**. Companies subscribe to a
plan and offer financial consulting to their employees, delivered by consultants
through scheduled appointments.

**Target audience:** companies (the tenants) and their employees (end users), served
by financial consultants.
**Channels:** Web only — four Filament panels (admin, app, company, consultant) plus
external integrations (Google Calendar, HighLevel).

## Business Model

- **SaaS multi-tenant, B2B2C.** A company subscribes to a tiered plan (billed via
  Laravel Cashier / Stripe and Barte) and makes financial consulting available to its
  employees.
- Employees consume credits / appointments according to the company plan; consultants
  deliver the sessions and post-session feedback.
- Plans and prices are **not** documented here — read `app-modules/billing` before
  quoting tiers or values.

## Modular Monolith (`internachi/modular`)

| Layer            | Modules                                                                 |
| ---------------- | ----------------------------------------------------------------------- |
| **Domain**       | `appointments`, `billing`, `company`, `consultants`, `permissions`, `support`, `tenant`, `user` |
| **Integration**  | `integration-google-calendar`, `integration-highlevel`                  |
| **Presentation** | `panel-admin`, `panel-app`, `panel-company`, `panel-consultant`         |

See the `module-architecture` guideline for the full layout and dependency rules.

## Key Conventions

- **Tenant = `Company`** — Filament-native tenancy, slug in the URL. See `multi-tenancy`.
- **Namespace:** `TresPontosTech\{Module}` (presentation modules → `TresPontosTech\Panel{Name}`).
- **Language:** PT-BR for user-facing strings, English for code.
- **PHP:** 8.4 (strict types). **Filament:** v5. **Livewire:** v4.
- **Testing:** Pest v4. **Quality:** PHPStan (modular, climbing levels), Rector, Pint.
