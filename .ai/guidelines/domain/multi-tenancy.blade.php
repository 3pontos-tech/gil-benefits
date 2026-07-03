# Multi-Tenancy

Filament-native tenancy. **The tenant is the `Company`** — there is no separate
`Tenant` model. (The `tenant` module holds supporting pieces: the `TenantMember` pivot
and the `HasTenant` trait, not a tenant entity.)

## Resolution

Filament resolves the tenant from the URL slug (`/{panel}/{company-slug}/…`). The
**app** and **company** panels enable tenancy:

@verbatim
<code-snippet name="Panel tenancy" lang="php">
// AppPanelProvider / CompanyPanelProvider
$panel->tenant(Company::class, slugAttribute: 'slug');
</code-snippet>
@endverbatim

The **admin** and **consultant** panels are **not** tenant-scoped.

The `User` model uses the `TresPontosTech\Tenant\Models\Traits\HasTenant` trait and
implements Filament's `HasTenants` + `HasDefaultTenant`. Its tenant relationship is
`companies()` (a `BelongsToMany` through the `TenantMember` pivot on the
`company_employees` table).

## Data isolation (hybrid)

There is **no** global `BelongsToTenant` trait and **no** `ApplyTenantScopes`
middleware. Isolation comes from two places:

1. **Filament automatic scoping** — any Resource registered in a tenant-enabled panel
   is auto-scoped via `whereBelongsTo($tenant)` while `$isScopedToTenant = true`
   (the default). Opt a resource out with `protected static bool $isScopedToTenant = false;`
   (e.g. `SharedDocumentResource`).
2. **Manual query scoping** — for everything outside Filament's automatic path, filter
   explicitly in `getEloquentQuery()` (e.g. `SupportTicketResource` filters by
   `auth()->id()`) or with `whereBelongsTo(filament()->getTenant())`.

Tenant-scoped models carry a `company_id` FK with a `company()` BelongsTo relationship
(e.g. `Appointment`, `SupportTicket`). Each model defines its own — there is no shared
trait.

## In tests

@verbatim
<code-snippet name="Tenant-scoped test setup" lang="php">
$company = Company::factory()->create();
$user = User::factory()->create();

filament()->setCurrentPanel('app');
filament()->setTenant($company);
$this->actingAs($user);

// Propagate the tenant through factory chains:
Appointment::factory()->recycle($company)->recycle($consultant)->create();
</code-snippet>
@endverbatim

Use `->recycle($company)` to reuse the same tenant across a factory chain, and
`filament()->setTenant($company)` to activate it for the panel under test.
