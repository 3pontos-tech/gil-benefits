---
type: adr
title: "Monthly appointment quota renews on a contract anchor date, not a rolling 30-day window"
module: billing
status: accepted
date: 2026-08-10
supersedes: none
affects:
  - billing
  - appointments
  - panel-app
  - panel-admin
---

# ADR: Monthly appointment quota renews on a contract anchor date

**Status:** accepted · **Date:** 2026-08-10 · **Decided by:** Richard (product/tech)

This record closes every decision needed to build the change. It states *what* was
decided and *why*, and carries an implementation map (schema, files, order, tests) so
that whoever implements it does not have to re-derive the reasoning. It is **not** a
task-by-task plan — if the implementer wants checkbox tasks in the style of
`app-modules/panel-app/docs/plans/`, that document should be generated from this one.

---

## 1. Context

### 1.1 How the quota works today

`User::monthlyAppointmentsLeft()` (`app/Models/Users/User.php`) computes the remaining
quota as:

```php
$used = $this->appointments()
    ->where('created_at', '>=', now()->subDays(30))
    ->where('status', '!=', AppointmentStatus::Cancelled->value)
    ->count();

return max($monthlyLimit - $used, 0);
```

This is a **rolling window relative to the read**, cached for one minute. Its practical
effect is that a slot frees up exactly 30 days after each booking, so every person has a
personal renewal date that silently drifts forward with every appointment they make. The
quota is therefore impossible to state to a customer ("you renew on the 10th" is never
true) and impossible to align with what billing actually charges.

`resolveMonthlyAppointmentLimit()` reads the limit from whichever comes first:

1. an active `CompanyPlan` for any company the user belongs to
   (`monthly_appointments_per_employee`), or
2. the user's own active subscription (`price.monthly_appointments`).

### 1.2 The two commercial models (important — they are not symmetric)

| Model | Who pays | Who subscribes | Grants quota? |
| --- | --- | --- | --- |
| **Contractual plan** (`company_plans`) | The company pays everything | Nobody — the employee subscribes to nothing | **Yes**, `monthly_appointments_per_employee` per employee |
| **Company plan** (`billing_plans.type = company`, Barte subscription held by the company) | The company subsidises *part of the price* | The **employee** subscribes, at the subsidised price | **No.** It only affects price and seat count |
| **Individual subscription** (held by the user) | The user | The user | **Yes**, `price.monthly_appointments` |

So quota has exactly **two** sources: the company's contractual plan, and the user's own
subscription. A company's own Barte subscription never produces quota for anyone; it is
consumed today only by `TenantSeatsCounterAction`, `CreateAndAttachAction` and
`ValidateUserImportAction` for seat limits.

### 1.3 What we are changing

> **Contractual plan:** anchor = the company plan's start date. The company and every
> employee attached to it renew on the same day of the month.
>
> **Individual plan:** anchor = the start/payment date of the person's own plan. It
> renews monthly on the same day it was contracted.
>
> Unused quota does **not** accumulate. A plan that grants one appointment per cycle
> grants one appointment in the next cycle too, never two.

### 1.4 Constraints discovered while designing this

These are facts about the current system that shaped the decisions. They are recorded
because they are not obvious from reading any single file.

1. **`company_plans.starts_at` is nullable and optional in the admin form**
   (`ContractualPlansRelationManager` declares `DatePicker::make('starts_at')` with no
   `->required()`). The field that is about to define every employee's renewal day can be
   empty today.
2. **`billing_subscriptions` has no billing-period columns at all** — only
   `stripe_status`, `stripe_price`, `quantity`, `trial_ends_at`, `ends_at`, timestamps.
3. **`ends_at` is not a period end, it is a cancellation date.** `UpsertSubscription`
   writes `$dto->endsAt`, and `HandleBarteWebhook` only sets it on
   `SUBSCRIPTION_INACTIVE` (as `Date::now()`); every other event passes `null` and the
   upsert overwrites it back to `null`. For an active subscription it is always `null`.
   `ends_at - 30 days` therefore cannot yield an anchor.
4. **Barte never tells us a subscription renewed.** The handled events are
   `SUBSCRIPTION_PENDING`, `SUBSCRIPTION_ACTIVE`, `SUBSCRIPTION_DEFAULTER`,
   `SUBSCRIPTION_INACTIVE`. There is no renewal or invoice-paid event, and `BarteClient`
   exposes only `getPlans`, `createBuyer`, `createPaymentLink`, `deleteSubscription` — no
   endpoint to read a subscription's next charge date.
5. **`created_at` on a subscription is not trustworthy as business data.** It is stamped
   at the first webhook (`PENDING`, i.e. *before* payment), and
   `app/Console/Commands/SyncSubscriptionToFlammaCompany.php` creates subscription rows by
   hand with a random `stripe_id` and no dates — for those rows `created_at` is the day
   somebody ran the command.
6. **There is no upper bound on how far ahead a person can book.** `PickSlotStep` and
   `RescheduleAppointmentAction` set only `minDate` (`Appointment::BOOKING_LEAD_DAYS = 2`);
   there is no `maxDate` anywhere in the codebase, and the calendar navigates forward
   indefinitely.
7. **A person can only ever have one open appointment.** `hasOngoingAppointment()` blocks
   booking while any appointment is not `completed`/`cancelled`/`cancelled_late`, and an
   appointment stays open until after its date. Booking is therefore serialised.
8. **`user_credits` is already a unit ledger** — one row per appointment, statuses
   `available / in_use / used / expired`, consumed automatically by `ConsumeCredit` when
   the monthly quota is zero, and displayed separately from plan quota in
   `PlanCreditsWidget`. The `expired` status exists but **nothing in the codebase ever
   assigns it**, and there is no expiry column: credits are perpetual today.
9. **The "which stock pays" decision is duplicated.**
   `BookAppointmentAction` decides via `$hasMonthlyQuota = $user->monthly_appointments_left > 0`
   and `panel-admin/.../Appointments/Pages/CreateAppointment.php` decides via
   `$this->consumesCredit = $user->monthly_appointments_left <= 0`.
10. **`ConsumeCredit` picks the oldest available credit** (`->oldest()`, i.e. by
    `created_at`) and silently no-ops when it finds none (`?->update(...)`).
11. **The customer-facing credits page shows no origin and no expiry.**
    `UserCreditsPage` lists status, `transferred_at` and `created_at` (labelled
    "purchased at").
12. **The active-contractual-plan query is copy-pasted in four places:**
    `User::resolveMonthlyAppointmentLimit()`, `PlanCreditsWidget::resolvePlan()`,
    `Company::activeContractualPlan()` and
    `panel-admin/src/Actions/Engagement/GetEngagementFunnel.php`.

---

## 2. Decisions

Each decision lists the alternatives that were on the table, so a future reader can see
what was rejected and why. The `Qn` references map to the decision log published for this
session.

### D1 — Quota is debited from the cycle of the **booking**, not the cycle of the appointment *(Q1)*

An appointment has two dates: `created_at` (when it was booked) and `appointment_at`
(when it happens). They can fall in different cycles. The quota is debited from the cycle
that contains **`created_at`**.

**Rejected — debit by `appointment_at`:** it loses the current cycle whenever the chosen
date falls after the turn. Someone who habitually books near the end of their cycle would
lose a paid cycle *every month*. It also lets a person spend a cycle that has not started
and may never be paid for, and it makes rescheduling move the debit between cycles,
which would force a reschedule to be refused when the target cycle is full.

**Rejected — only allow dates inside the current cycle:** `BOOKING_LEAD_DAYS = 2` means
that on the last two days of every cycle no valid date exists inside it. This would create
a two-day dead zone every month in which nobody can book at all.

**Consequence, accepted:** in the boundary case a person can have two appointments
*happen* inside one calendar month (they paid for two cycles, so this is correct, not
abuse). Rescheduling never touches the quota.

### D2 — Cycles step by calendar month from the original anchor, clamped, without drift *(Q4)*

The nth cycle starts at `anchor + n months`, computed **from the original anchor** with
no month overflow. February clamps to the last day of the month and the following months
return to the original day.

Anchor 31 Jan → 28 Feb → **31** Mar → 30 Apr → **31** May.

**Rejected — drift:** clamping and then stepping from the clamped result (31 Jan → 28 Feb
→ 28 Mar → 28 Apr …) makes the renewal day slide backwards permanently, so a customer who
contracted on the 31st ends up renewing on the 28th forever, which is not what billing
does.

**Rejected — fixed 30-day blocks:** the renewal date then moves every month and the
customer cannot predict it.

### D3 — Contractual anchor is `company_plans.starts_at`, which becomes required *(Q2)*

`starts_at` becomes required in the admin form, and existing rows with a null value are
backfilled with the plan's `created_at`.

**Rejected — silent runtime fallback to `created_at`:** the field now has a
customer-visible effect (the day an employee gets their appointment back). Leaving the
record incomplete and papering over it in code makes that effect unauditable.

**Rejected — default to the 1st of the month:** two companies with contracts starting on
different dates would renew on the same day, and the 1st has no relationship to either
contract.

### D4 — Individual anchor is a new dedicated column, stamped at activation *(Q3)*

Add `billing_subscriptions.quota_anchor_at`. It is written **once**, when the
subscription's status first becomes `active`, and never modified afterwards. Existing
subscriptions are backfilled with `created_at`.

**Rejected — use `created_at` directly.** The precision argument for the dedicated column
is weak and was correctly challenged: the gap between "subscription created" and
"subscription paid" is the pix/boleto window, 1–3 days, and it errs in the customer's
favour (the cycle starts slightly early). The reason for the column is different:
`created_at` is infrastructure metadata that the system can rewrite on its own (see
constraint 1.4.5) and that nobody can correct for a single customer without falsifying the
row's history. A dedicated column can be corrected, and it preserves the distinction
between an anchor we *observed* and an anchor we *guessed at* during backfill.

**Rejected — fetch the date from Barte's API:** would need a new client method and we do
not know whether the endpoint exposes it. Reconsider only if the anchor turns out to be
wrong in practice.

**Rejected — a `current_period_start` advanced on each payment:** Barte sends no renewal
event (1.4.4), so the column would never be updated.

### D5 — Quota has exactly two sources *(Q5)*

Only the company's contractual plan and the user's own subscription produce quota. A
company's Barte subscription stays what it is today: price subsidy plus seat count.

**Rejected — company subscription grants quota to employees:** that is a new feature, not
a renewal change. It would first need answers to: is `price.monthly_appointments` per
employee or for the whole company; what happens when active employees exceed
`quantity`; and how it combines with a contractual plan on the same company.

### D6 — The contractual plan keeps precedence over an individual subscription *(Q6)*

Unchanged behaviour, and there is a test pinning it
(`tests/Feature/UserMonthlyAppointmentsLeftTest.php`: company quota 1 wins over
individual quota 5).

The combination is in practice impossible: `RedirectUserIfNotSubscribed` sends the user
away from checkout when `$tenant->hasActivePlan()`, so an employee covered by a
contractual plan is never offered a subscription in that tenant.

**Known residue (not addressed here):** the guard is per tenant, while the subscription
hangs off the user with no tenant. Somebody who subscribed in one tenant and later became
a covered employee in another lands in the "impossible" combination. Precedence handles it
safely, and which company is used is settled by D7.

### D7 — The company (and therefore the anchor) is the one selected on screen *(Q9)*

Resolve the contractual plan from the **current Filament tenant**, falling back to
`employerCompanyId()` when there is no tenant (console, jobs, mail).

Today the query is `whereIn('company_id', $user->companies())->first()` with no ordering,
so the row — and now the renewal day — is whatever the database returns first. Every
user also belongs to the shared default company, so this also closes a latent hazard: if
the default company ever got an active contractual plan, it could become everyone's quota
source.

**Rejected — always `employerCompanyId()`:** `PlanCreditsWidget` and `UserCreditsPage`
already filter credits by the selected tenant; taking the quota from a different company
would show two contradictory numbers on the same screen.

**Rejected — the company with the largest quota:** the renewal day would then change on
its own whenever either contract is edited.

**Product residue noted by the decider:** switching to the default tenant while covered by
a subsidised company shows a worse (or absent) plan and an unsubsidised price. Worth
revisiting whether the default company should appear in the tenant switcher at all — out
of scope here.

### D8 — An employee joining mid-cycle gets the full quota *(Q7)*

No proration. If the company renews on the 10th and the employee is registered on the 7th,
they may **book** until the 9th using that partial cycle, and the appointment itself may
happen after the 10th. If they do not book by the 9th, that cycle is lost — it never
becomes two.

**Rejected — proration:** with the typical quota of 1, proration rounds to 0 and becomes
"no quota", which is the other rejected option.

**Rejected — no quota until the first turn:** a newly registered employee opens the app,
sees an active plan and cannot book anything, with no obvious explanation.

### D9 — The cancellation policy is unchanged *(Q8)*

* More than `CANCELLATION_WINDOW_HOURS` (4h) before the appointment → `cancelled`; leaves
  the count, the person recovers the appointment.
* Less than 4h → `cancelled_late`; still counts, the person loses it.
* Admin/system cancellations are never penalised (`resolveCancellationStatus` only
  consults `isLateCancellation()` for `CancellationActor::User`).

### D10 — Bookings are capped at 45 days ahead, counted from today *(Q11, Q13)*

Introduce a fixed 45-day horizon, enforced in `AppointmentWizard::availableSlots()` so
that both the booking wizard and the reschedule flow inherit it, plus the server-side
`isBookableSlot()` check. The horizon is always counted **from now**, including on each
reschedule.

Why a cap at all: with no `maxDate` (1.4.6) and one-open-appointment-at-a-time (1.4.7),
booking 90 days out burns today's quota and then silently erases the two cycles in
between. The cap bounds that damage to a little over one cycle.

**Rejected — count the 45 days from the original booking:** it punishes good faith. Someone
who booked 40 days out and needs to move has a 5-day window that may contain no
consultant availability at all, leaving cancellation as the only exit. It also requires
threading the appointment into `availableSlots()` and into the server-side validation,
which are shared and currently appointment-agnostic; if the two drift, the calendar offers
slots the server rejects.

**Accepted residue:** a person can push an appointment forward indefinitely by
rescheduling. This is self-punishing (the quota was already debited, and they stay blocked
from booking anything else) but it does hold consultant availability. If that becomes a
real problem, the remedy is a limit on the **number** of reschedules, not a narrower date
horizon.

### D11 — The cycle is computed on read; nothing is materialised *(Q14a)*

No `quota_periods` table. The current cycle is derived from the anchor at read time and
the quota remains "limit − bookings created inside the current cycle".

The only thing that would have required persistence was crediting a cancellation back
into a cycle other than the one debited, and that moved to the credit ledger (D13).

### D12 — Admin changes apply to the cycle already in progress *(Q14b)*

Because the cycle is derived on read, the current values always win: raising
`monthly_appointments_per_employee` mid-cycle immediately gives the extra appointment in
the running cycle, and correcting `starts_at` re-slices the window (an appointment that
falls outside the new window returns quota).

This is what the system already does today, both situations are rare and admin-driven, and
the deviation is one appointment, almost always in the customer's favour. Preventing it
would require the table rejected in D11.

### D13 — A cancellation whose debited cycle has already closed produces an expiring credit *(Q12)*

**The case.** Renewal on the 10th. The person books on **9 Sep** (last day of their cycle)
an appointment for **12 Sep**; the debit sits in the cycle that closed on the 9th. On
**11 Sep**, already inside the new cycle, they cancel with more than 4 hours' notice.
"Recovering the appointment of the month" would credit a cycle that no longer exists: they
followed the rules and got nothing.

The refund is issued as a **`UserCredit` with an expiry date**, not as an adjustment to
the plan quota.

**Rejected — refund nothing:** the person cancelled within the rules. Note this is a
*regression introduced by this change*: with today's rolling window, cancelling always
frees the slot because the window is relative to the read.

**Rejected — a perpetual credit:** it would become an accumulating balance, which the
whole change exists to prevent.

**Rejected — refund inside the plan quota:** the plan limit is what the price sold; it
must not move. And expressing "+1 in the current cycle" would require the materialised
table rejected in D11.

### D14 — The refund credit expires at the end of the person's current cycle *(open point 6)*

Validity = the end of the cycle that is running at the moment of cancellation. Cancelled
inside a cycle, used inside that cycle. This is what stops it becoming an accumulating
balance.

### D15 — The refund credit is spent before the monthly quota, and the "which stock pays" decision is unified first *(open point 1)*

Two parts:

1. **Ordering among credits (mandatory).** `ConsumeCredit` currently takes the *oldest*
   credit, which would put a freshly created refund credit last in line — it would expire
   while perpetual gift credits sit untouched. The order becomes: credits with an expiry
   first (soonest first), then perpetual credits, then by `created_at`.
2. **Credit before monthly quota (decided, with a prerequisite).** The duplicated
   decision (1.4.9) must be unified into a single place *before* this is implemented,
   otherwise the app flow and the admin panel flow will diverge.

For the record: with D14, the refund credit and the monthly quota expire at the same
instant, so today this priority does not change any outcome — a person who books once in
the cycle loses the other either way, and a person who books twice uses both regardless of
order. It was chosen anyway so the behaviour is already correct if a credit with a
mid-cycle expiry is ever issued.

### D16 — A returned refund credit keeps its original expiry *(open point 5)*

When an appointment paid for with a refund credit is itself cancelled in time,
`ReturnCreditOnAppointmentCancelledListener` returns the credit **with its original
expiry**, even if that date has already passed — in which case the credit is dead on
arrival and the person loses it.

**Rejected — extend the expiry to the end of the current cycle on return.** It was
recommended, on the grounds that it repeats one level down exactly the unfairness D13
fixes, and was declined as not worth the extra rule.

**Interaction to be aware of:** because D15 spends credits *before* the monthly quota,
the refund credit is more often the one in use when a cancellation happens, so this case
occurs more often than it would have with quota-first ordering.

### D17 — The refund is recorded as a `CreditGrant` with an explicit `reason` *(open points 2, 3, 4)*

* Add `credit_grants.reason`, backed by a new `CreditGrantReasonEnum` (billing module
  already uses the `Enum` suffix: `UserCreditStatusEnum`, `CompanyPlanStatusEnum`).
  Initial values: `AdminGift` (everything that exists today) and `QuotaRefund`.
  Room for `Promotion`, `Compensation`, `Migration` later without a migration.
* `company_id` is the cancelled appointment's company; `target_user_id` and the credit's
  `owner_id`/`holder_id` are the person; `admin_user_id` is null.
* `justification` (already `NOT NULL`) is **composed from the case**, not a fixed
  sentence — e.g. "automatic refund — appointment of 12/09, booked 09/09, cancelled 11/09".
* A **dedicated action** issues it, reusing `IssueCredits`. `GrantExtraCredit` is
  documented admin-only and validates a typed justification; calling it from a
  cancellation would mix the two roles.

The `reason` column is what makes filtering possible, and it replaces the
inference-by-absence (`admin_user_id IS NULL`) that would otherwise be needed.
Because `user_credits.grant_id` already exists, no origin column is needed on the credit
itself.

**Rejected — record the origin on the credit and skip the grant:** was recommended,
because the grant's stated purpose (carrying the company) is already served by
`user_credits.company_id`, and because reusing the donations screen forces filters onto
three existing surfaces. It was declined in favour of keeping the audit trail where the
admin already looks; the `reason` enum makes that safe.

**Rejected — create the grant and leave it mixed in with donations:** the donations
listing means "how much we gave away", and a refund is not a donation.

### D18 — No data migration at cutover *(Q10)*

Ship it and accept the one-cycle discrepancy.

* **Common, in the customer's favour:** cycle started 5 days ago, appointment used 20 days
  ago — was zero, becomes 1 immediately.
* **Rare, against:** in 31-day months the cycle may have started 31 days ago while the old
  window looked back only 30, so an appointment made 30½ days ago leaves the old count and
  enters the new one, keeping the person at zero one more day.

**Rejected — a cutover script zeroing whoever used an appointment in the last 30 days**
and **rejected — running both rules until each plan's next turn**: both need temporary
code that someone must remember to remove, and the second keeps two rules alive at once.

---

## 3. Worked examples (use these as test cases)

Renewal day 10 unless stated otherwise.

| # | Scenario | Expected |
| --- | --- | --- |
| 1 | Cycle 10 Aug–9 Sep. Books 9 Sep for 12 Sep. | Debit lands in the 10 Aug–9 Sep cycle. The 10 Sep cycle opens with full quota. |
| 2 | Same, then the appointment completes on 12 Sep. | Person may book again inside the 10 Sep–9 Oct cycle. Two appointments happen in September; two cycles were paid for. |
| 3 | Same, but cancelled on 11 Sep with >4h notice. | The debited cycle has closed → issue a `QuotaRefund` credit expiring 9 Oct (D13, D14). |
| 4 | Anchor 31 Jan. | Cycle starts: 28 Feb, 31 Mar, 30 Apr, 31 May (D2). |
| 5 | Employee registered on the 7th, company renews on the 10th. | Full quota; must **book** by the 9th; the appointment may occur after the 10th; unused → lost, never doubled (D8). |
| 6 | Renewal day 18. Books 7 Aug for 20 Aug, reschedules on 19 Aug to 5 Sep. | Debit stays in 18 Jul–17 Aug. The 18 Aug–17 Sep cycle is **not** debited; the person is merely blocked until 5 Sep, then may book again until 17 Sep. Never accumulates: on completion they have 1 available, not 3. |
| 7 | Person has an active contractual plan (1) and an active individual subscription (5). | Result is 1 (D6) — existing test must keep passing. |
| 8 | Person is an employee of two companies with active contractual plans. | The quota and the anchor come from the selected tenant (D7). |
| 9 | Admin raises `monthly_appointments_per_employee` from 1 to 2 on the 20th; the person used theirs on the 12th. | 1 available immediately, in the running cycle (D12). |
| 10 | Person has one perpetual gift credit and one `QuotaRefund` credit expiring in 3 days. | `ConsumeCredit` takes the expiring one (D15.1). |
| 11 | Appointment paid with a refund credit is cancelled in time, after the credit's expiry. | Credit returns to `available` with the original (past) expiry and is therefore unusable (D16). |
| 12 | Booking attempt for a date 60 days out. | Rejected by both the calendar and `isBookableSlot()` (D10). |
| 13 | Contractual plan with `starts_at` null (pre-backfill data). | Must not happen after the migration; the backfill sets `created_at` (D3). |

---

## 4. Consequences

**Improves**

* The renewal date becomes a stable, explainable fact ("you renew on the 10th") aligned
  with the contract instead of drifting with usage.
* The company and all its employees share one renewal day, which is what the contractual
  product promises.
* The arbitrary `first()` company pick disappears, along with the latent default-company
  hazard.
* Booking gains an upper horizon, closing the silent multi-cycle loss.

**Regresses / costs**

* Between phase 1 and phase 2 (see §5.1) a cancellation made after the turn refunds
  nothing — a case that does not exist today.
* `user_credits` gains an expiry concept, which means the expiry filter must be applied in
  **every** read (see the pitfall in §5.5).
* The donations screens must filter by `reason` forever; any future report that forgets it
  will count refunds as donations.

**Accepted risks**

* The one-minute cache on `monthly_appointments_left` can straddle a cycle boundary. The
  stale value lasts at most 60 seconds; not worth invalidating on a schedule.
* D12 means an admin edit rewrites the running cycle. Rare, admin-driven, one appointment.
* D16 means a refund credit returned after its expiry is lost.

---

## 5. Implementation map

### 5.1 Suggested phasing

**Phase 1 — the anchor and the window (no credit code at all).** D1–D12, D18. This is
self-contained and is where all the edge cases live.

**Phase 2 — the refund.** D13–D17.

Phase 1 can ship alone, at the cost of the regression named in §4. Keep the gap short.

### 5.2 Schema changes

| Table | Change |
| --- | --- |
| `company_plans` | No schema change. `starts_at` stays a nullable `date` in the DB; the requirement is enforced in the admin form. Backfill: `UPDATE company_plans SET starts_at = DATE(created_at) WHERE starts_at IS NULL`. |
| `billing_subscriptions` | Add `quota_anchor_at` (nullable `timestamp`). Backfill with `created_at`. Nullable because a subscription may exist before it is ever activated. |
| `credit_grants` | Add `reason` (`string`, `NOT NULL`, default `admin_gift`). Use a default even though production is believed to have no grants — a single row in any environment would otherwise break the migration. Cast to `CreditGrantReasonEnum`. |
| `user_credits` | Add `expires_at` (**nullable** `timestamp`). Null means "never expires", which is the existing gift-credit semantics and must stay the default. |

`CreditGrantFactory` must start setting `reason`.

### 5.3 New code

* **`CreditGrantReasonEnum`** — `app-modules/billing/src/Core/Enums/`. Values `AdminGift`,
  `QuotaRefund`. Implement `HasLabel` (and `HasColor` if it is shown as a badge), with
  translations in `app-modules/billing/lang/{pt_BR,en}/enums.php`, following
  `UserCreditStatusEnum`.
* **A cycle value object** — suggested `QuotaCycle` in the billing module, given an anchor
  date and "now", returning the current period's start and end. This is the single place
  D2's clamping logic lives. Implementation note: step from the **original** anchor
  (`anchor->copy()->addMonthsNoOverflow($n)`), never from the previously computed start,
  or D2's "no drift" requirement is violated.
* **A single active-contractual-plan resolver** — the query is copy-pasted in four places
  (1.4.12). Give it one home (an Eloquent scope on `CompanyPlan`, or a small action) and
  have all four call it, so D7's tenant rule cannot be applied inconsistently.
* **A refund action** — creates the `CreditGrant` with `reason = QuotaRefund` and calls
  `IssueCredits` with `expires_at`. Do not extend `GrantExtraCredit` (D17).
* **A single "which stock pays" resolver** — required by D15.2, replacing the duplicated
  logic in `BookAppointmentAction` and panel-admin's `CreateAppointment`.

### 5.4 Files to touch

**Quota calculation (D1, D2, D5, D6, D7, D8, D11, D12)**
* `app/Models/Users/User.php` — `monthlyAppointmentsLeft()` (window), `resolveMonthlyAppointmentLimit()` (tenant-scoped plan + anchor), `hasAvailableCredit()` (expiry filter, phase 2).

**Contractual anchor (D3)**
* `app-modules/panel-admin/src/Filament/Resources/Companies/RelationManagers/ContractualPlansRelationManager.php` — `starts_at` becomes `->required()`.
* Backfill migration.

**Individual anchor (D4)**
* Migration for `quota_anchor_at`.
* `app-modules/billing/src/Core/Actions/UpsertSubscription.php` — stamp it when the status first becomes `active` and the column is null. `SubscriptionDTO` does not need to carry it; the action knows the status.
* `app-modules/billing/src/Core/Models/Subscriptions/Subscription.php` — property docblock and cast.

**45-day horizon (D10)**
* `app-modules/panel-app/src/Filament/Resources/Appointments/Schemas/AppointmentWizard.php` — `availableSlots()` returns `[]` beyond the horizon; `isBookableSlot()` inherits it.
* `app-modules/panel-app/src/Filament/Resources/Appointments/Schemas/PickSlotStep.php` — pass a `maxDate` to the calendar view.
* `app-modules/panel-app/src/Filament/Actions/RescheduleAppointmentAction.php` — same `maxDate` alongside the existing `minDate`.
* `resources/views/filament/app/appointments/wizard/calendar-field.blade.php` — accepts `max` next to the existing `min`.
* Consider a `BOOKING_HORIZON_DAYS = 45` constant on `Appointment`, beside `BOOKING_LEAD_DAYS`.

**Refund (D13–D17, phase 2)**
* `app-modules/appointments/src/Actions/Transitions/AbstractAppointmentTransition.php` — in `cancelProcessStep()`, when the status becomes `cancelled` **and** the cycle containing the appointment's `created_at` has already closed, dispatch the refund. Keep it as an event/listener, consistent with `AppointmentCreditReturned`/`AppointmentCreditUsed`.
* `app-modules/billing/src/Core/Actions/Credit/ConsumeCredit.php` — ordering (D15.1) and expiry filter.
* `app-modules/billing/src/Core/Listeners/Credit/ReturnCreditOnAppointmentCancelledListener.php` — no change; D16 is the decision to leave the expiry untouched. Add a test pinning it.
* `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php` — expiry filter on the credit count.
* `app-modules/panel-app/src/Filament/Pages/UserCreditsPage.php` — expiry filter, an `expires_at` column, and fix the `created_at` label (currently "purchased at", wrong for a refund).
* `app-modules/panel-admin/src/Filament/Resources/CreditGrants/` (resource + `ListCreditGrants`) — `reason` column/filter; hide revoke when `reason !== AdminGift`.
* `app-modules/panel-admin/src/Filament/Resources/Companies/RelationManagers/CreditGrantsRelationManager.php` and `.../Users/RelationManagers/CreditGrantsRelationManager.php` — same filtering.
* `app-modules/billing/src/Core/Actions/Credit/RevokeCreditGrant.php` — guard against revoking a non-`AdminGift` grant.
* `app-modules/appointments/src/Actions/BookAppointmentAction.php` and `app-modules/panel-admin/src/Filament/Resources/Appointments/Pages/CreateAppointment.php` — replace the duplicated decision with the shared resolver (D15.2).

**Expiry status**
Nothing sets `UserCreditStatusEnum::Expired` today. Correctness comes from filtering
reads by `expires_at IS NULL OR expires_at >= now()`; a scheduled command stamping the
status is optional and only serves the history shown in "My Credits".

### 5.5 Pitfall that must not be missed

`ConsumeCredit` no-ops silently when it finds no credit (`?->update(...)`), while
`canCreateAppointment()` decides *whether* booking is allowed. If the expiry filter is
added to one and not the other, a person with only expired credits will be allowed to
book and **nothing will be debited**. The filter must land in all of:
`User::hasAvailableCredit()`, `ConsumeCredit`, `PlanCreditsWidget`, `UserCreditsPage`.
Extracting it into a single query scope on `UserCredit` (e.g. `usable()`) is the safest
way to guarantee that.

### 5.6 Existing tests that constrain the change

* `tests/Feature/UserMonthlyAppointmentsLeftTest.php` — pins D6.
* `app-modules/appointments/tests/Feature/Actions/BookAppointmentActionCreditTest.php` and
  `app-modules/panel-admin/tests/Feature/Filament/Resources/Appointments/CreateAppointmentCreditTest.php`
  — both rely on "a user with no company/plan always has `monthly_appointments_left = 0`",
  which must remain true.
* `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php` —
  asserts `canCreateAppointment` and the block reasons share one computation.
* `app-modules/billing/tests/Feature/CompanyPlan/ActiveContractualPlanTest.php` — will be
  affected by the single resolver in §5.3.

---

## 6. Deliberately out of scope

Recorded so they are not mistaken for oversights:

1. A company's Barte subscription granting quota to employees (D5).
2. A limit on the number of reschedules per appointment (D10's residue).
3. Whether the default company should appear in the tenant switcher (D7's residue).
4. Summing contractual and individual quota for the same person (D6).
5. A scheduled command that stamps `UserCreditStatusEnum::Expired` (§5.4).
