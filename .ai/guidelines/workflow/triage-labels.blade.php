# Triage Labels

The skills speak in terms of five canonical triage roles. This file maps those roles to
the actual label strings used in this repo's issue tracker.

| Label in skills   | Label in our tracker | Meaning                                  |
| ----------------- | -------------------- | ---------------------------------------- |
| `needs-triage`    | `needs-triage`       | Maintainer needs to evaluate this issue  |
| `needs-info`      | `needs-info`         | Waiting on reporter for more information |
| `ready-for-agent` | `ready-for-agent`    | Fully specified, ready for an AFK agent  |
| `ready-for-human` | `ready-for-human`    | Requires human implementation            |
| `wontfix`         | `wontfix`            | Will not be actioned                     |

When a skill mentions a role (e.g. "apply the AFK-ready triage label"), use the
corresponding label string from this table.

---

# Type Labels

Issue type follows conventional commit prefixes.

| Label           | Meaning                       |
| --------------- | ----------------------------- |
| `type:feat`     | New feature                   |
| `type:fix`      | Bug fix                       |
| `type:refactor` | Code refactoring              |
| `type:docs`     | Documentation                 |
| `type:prd`      | Product Requirements Document |
| `type:chore`    | Maintenance / tooling         |
| `type:ci`       | CI / build pipeline           |

---

# Module Labels

Every issue must be tagged with the module(s) it affects. Labels follow the pattern
`mod:<module-name>`, matching the directory name under `app-modules/`.

| Label                              | Module directory               | Description               |
| ---------------------------------- | ------------------------------ | ------------------------- |
| `mod:appointments`                 | `appointments`                 | Scheduling & appointments |
| `mod:billing`                      | `billing`                      | Subscriptions & billing   |
| `mod:company`                      | `company`                      | Companies (tenants)       |
| `mod:consultants`                  | `consultants`                  | Consultants & documents   |
| `mod:integration-google-calendar` | `integration-google-calendar`  | Google Calendar sync      |
| `mod:integration-highlevel`        | `integration-highlevel`        | HighLevel integration     |
| `mod:panel-admin`                  | `panel-admin`                  | Admin Filament panel      |
| `mod:panel-app`                    | `panel-app`                    | End-user Filament panel   |
| `mod:panel-company`                | `panel-company`                | Company Filament panel    |
| `mod:panel-consultant`             | `panel-consultant`             | Consultant Filament panel |
| `mod:permissions`                  | `permissions`                  | RBAC & permissions        |
| `mod:support`                      | `support`                      | Support tickets           |
| `mod:tenant`                       | `tenant`                       | Tenancy scaffolding       |
| `mod:user`                         | `user`                         | Users & anamnese          |

When creating an issue for a new module that has no label yet, create the label first
(`gh label create "mod:<name>" --description "<short description>" --color "c2e0c6"`)
and add a row to this table.

---

# Difficulty Labels

Every implementable issue should be tagged with a difficulty estimate.

| Label                | Estimate  | Meaning                                          |
| -------------------- | --------- | ------------------------------------------------ |
| `difficulty:trivial` | < 1 day   | Deletion, config changes, scripts                |
| `difficulty:easy`    | 1-2 days  | Single model/action, well-defined scope          |
| `difficulty:medium`  | 3-5 days  | Multiple files, Filament UI, moderate complexity |
| `difficulty:hard`    | 1-2 weeks | Cross-module, complex logic, multiple panels     |
| `difficulty:epic`    | 2+ weeks  | Entire new system, major refactors               |

Issues tagged `difficulty:trivial` or `difficulty:easy` should also receive the
`good first issue` label.

---

# Title Convention

Issue titles follow **conventional commits** with the module as scope:

```
<type>(<module>): <short description in English>
```

Examples:
- `feat(appointments): reschedule flow with Google Calendar sync`
- `refactor(billing): consolidate plan tier resolution`
- `fix(panel-company): tenant slug not resolved on invite link`
- `prd(consultants): document sharing MVP`
