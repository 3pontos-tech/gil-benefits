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

**Status:** accepted · **Date:** 2026-08-10 · **Revised:** 2026-08-16 during implementation · **Decided by:** Richard (product/tech)

This record closes every decision needed to build the change. It states *what* was
decided and *why*, and carries an implementation map (schema, files, order, tests) so
that whoever implements it does not have to re-derive the reasoning. It is **not** a
task-by-task plan — for checkbox tasks see
`app-modules/billing/docs/plans/2026-08-14-monthly-quota-renewal-anchor-followup.md`.

**What changed on 2026-08-16.** Implementation invalidated four decisions, and they are
revised in place rather than left to mislead the next reader:

* **D13** replaces the original D13–D17. The refund stopped going through the credit ledger
  and became one stamped column plus one arithmetic term, which removed D14, D15, D16, D17,
  three screens' worth of filtering and the silent-no-op hazard around `ConsumeCredit`. The
  premise that had ruled this out — that "+1 in the current cycle" needed the materialised
  table rejected in D11 — was wrong.
* **D10** is withdrawn. The multi-cycle loss it claimed to contain is caused by
  `hasOngoingAppointment()` and exists identically before and after this change.
* **D4** keeps its conclusion but loses one of its three justifications, and gains the
  reason the stamp lives in a model observer rather than in `UpsertSubscription`.
* **D18** keeps its conclusion and gains a measurement step before the deploy.

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
4. **No provider tells us a subscription renewed — but Virtu comes close.** Barte's handled
   events are `SUBSCRIPTION_PENDING`, `SUBSCRIPTION_ACTIVE`, `SUBSCRIPTION_DEFAULTER`,
   `SUBSCRIPTION_INACTIVE`: no renewal or invoice-paid event, and `BarteClient` exposes no
   endpoint to read a subscription's next charge date.

   *Updated 2026-08-16.* Virtu (#258) is now the only provider for new checkouts, and it
   has no subscription lifecycle event at all — a recurring charge simply arrives as another
   `TRANSACTION` with `data.subscriptions` populated. So a **payment** for an individual
   subscription is now observable, which Barte never gave us. It still is not a renewal of
   the quota cycle: the cycle is anchored on `quota_anchor_at`, not on the charge, and
   contractual plans — where most employees live — produce no payment event whatsoever.
   Enough to reconsider the renewal e-mail (issue #254), not enough to change D11.
5. **`created_at` on a subscription is stamped before payment.** The row is created at the
   first webhook (`PENDING`), so for pix/boleto it precedes the payment by 1–3 days.
   Separately, `app/Console/Commands/SyncSubscriptionToFlammaCompany.php` creates rows by
   hand with a random `stripe_id` and no dates — but those belong to the **company**, and a
   company subscription never produces quota (D5), so they never reach the quota path.
6. **The three providers share one table and one status column.** `billing_subscriptions`
   keeps Cashier's column names (`stripe_id`, `stripe_status`, `stripe_price`) and has no
   provider column. Barte and Virtu write the same vocabulary
   (`pending|active|defaulter|inactive`); Stripe writes its own
   (`active|trialing|past_due|canceled|incomplete|…`). `active` is the only value shared by
   all three. Barte and Virtu enter through `UpsertSubscription`; Stripe is written by
   Cashier's `WebhookController` directly, never through it.
7. **There is no upper bound on how far ahead a person can book.** `PickSlotStep` and
   `RescheduleAppointmentAction` set only `minDate` (`Appointment::BOOKING_LEAD_DAYS = 2`);
   there is no `maxDate` anywhere in the codebase, and the calendar navigates forward
   indefinitely.
8. **A person can only ever have one open appointment.** `hasOngoingAppointment()` blocks
   booking while any appointment is not `completed`/`cancelled`/`cancelled_late`, and an
   appointment stays open until after its date. Booking is therefore serialised.
9. **`user_credits` is already a unit ledger** — one row per appointment, statuses
   `available / in_use / used / expired`, consumed automatically by `ConsumeCredit` when
   the monthly quota is zero, and displayed separately from plan quota in
   `PlanCreditsWidget`. The `expired` status exists but **nothing in the codebase ever
   assigns it**, and there is no expiry column: credits are perpetual today.
10. **The "which stock pays" decision is duplicated.**
   `BookAppointmentAction` decides via `$hasMonthlyQuota = $user->monthly_appointments_left > 0`
   and `panel-admin/.../Appointments/Pages/CreateAppointment.php` decides via
   `$this->consumesCredit = $user->monthly_appointments_left <= 0`.
11. **`ConsumeCredit` picks the oldest available credit** (`->oldest()`, i.e. by
    `created_at`) and silently no-ops when it finds none (`?->update(...)`).
12. **The customer-facing credits page shows no origin and no expiry.**
    `UserCreditsPage` lists status, `transferred_at` and `created_at` (labelled
    "purchased at").
13. **The active-contractual-plan query is copy-pasted in four places:**
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

**Amended 2026-09-04 — a `?? created_at` guard survived in `ResolveQuotaAllowance`.** The
resolver reads `$plan->starts_at ?? $plan->created_at`, and the same shape for
`quota_anchor_at`. That is literally the expression this decision rejects, so it is worth
stating plainly: it is a guard against a null column, not the policy. The policy holds —
the admin form requires the field, the backfill filled every existing row, and there is no
other production write path to `company_plans`. What keeps the guard is that both columns
are still nullable in the schema, and `CarbonImmutable::instance(null)` throws. The branch
is reachable only from `CompanyPlanFactory`, whose default leaves `starts_at` null, and
therefore from `EssentialsSeeder`. Delete it if either column ever becomes `NOT NULL`.

**Rejected — default to the 1st of the month:** two companies with contracts starting on
different dates would renew on the same day, and the 1st has no relationship to either
contract.

### D4 — Individual anchor is a new dedicated column, stamped at activation *(Q3)*

Add `billing_subscriptions.quota_anchor_at`. It is written **once**, when the
subscription's status first becomes `active`, and never modified afterwards. Existing
subscriptions are backfilled with `created_at`.

**Where the stamp lives (revised 2026-08-16).** In a model observer on `Subscription`
(`saving`), not in `UpsertSubscription` as first planned. There is more than one write
path and they do not know about each other: Barte enters through `UpsertSubscription`,
Stripe enters through Cashier's `WebhookController`, which writes to the table directly,
and rows are also created by console command and by factory. Stamping in any single writer
would leave the others without an anchor.

The filter is `stripe_status = 'active'`. Both providers share this table and this column
with different vocabularies — `pending|active|defaulter|inactive` against
`active|trialing|past_due|canceled|incomplete|…` — and `active` is the only value common
to both. It is also the value `User::activeSubscription()` already uses to decide who has
quota, so the two reads stay in agreement. Nothing guarantees this beyond the coincidence,
which is why it is written down.

**Rejected — use `created_at` directly.** The precision argument for the dedicated column
is weak and was correctly challenged: the gap between "subscription created" and
"subscription paid" is the pix/boleto window, 1–3 days, and it errs in the customer's
favour (the cycle starts slightly early). The reason for the column is that `created_at`
is infrastructure metadata nobody can correct for a single customer without falsifying the
row's history. A dedicated column can be corrected, and it preserves the distinction
between an anchor we *observed* and an anchor we *guessed at* during backfill.

*Correction:* the original text also cited `SyncSubscriptionToFlammaCompany` (constraint
1.4.5) as evidence that `created_at` is untrustworthy. That does not apply here — the rows
that command creates belong to the **company**, and a company subscription never produces
quota (D5), so they never reach this code path.

**Rejected — fetch the date from the provider's API:** would need a new client method per
provider, and none of the three exposes a next-charge endpoint we know of. Reconsider only
if the anchor turns out to be wrong in practice.

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

**Amended 2026-09-04 — the consumption count is scoped by that same company.** Resolving
the *allowance* per tenant while counting `User::appointments()` unscoped mixed the two:
someone employed at two companies had one company's limit debited by both companies'
bookings, and a refund stamped at one appeared in the other's balance. `QuotaAllowance`
now carries the resolved `companyId`, and both the consumption count and the refund count
filter on it. That filter is the same expression `BookAppointmentAction` writes into
`appointments.company_id` (`$payload->companyId ?? $user->employerCompanyId()`), on
purpose: what records a consumption and what counts it have to agree. A null company —
an individual subscriber with no employer — matches the bookings created without one.

The refund path resolves its allowance from `appointments.company_id` rather than from the
ambient tenant. A cancellation can arrive from the admin panel, from a job, or while the
person has another company selected, and each of those would otherwise read a different
anchor and return the appointment to the wrong cycle.

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

*Updated 2026-08-16.* `no_show` (#267) joined the status set and behaves as US-385 asks:
the count excludes only `cancelled`, so a no-show consumes the cycle. It also never
refunds, because the stamp of D13 lives in `cancelProcessStep()` and a no-show does not go
through it. Pinned by a test.

### D10 — *Withdrawn.* Bookings are not capped *(Q11, Q13)*

> **Withdrawn 2026-08-16, during implementation.**

The original decision introduced a fixed 45-day booking horizon, justified by the claim
that with no `maxDate` (1.4.7) and one open appointment at a time (1.4.8), booking 90 days
out "burns today's quota and then silently erases the two cycles in between".

**The justification does not hold.** That erasure is caused by `hasOngoingAppointment()`,
which this change does not touch. Booking on 1 January for 1 April locks the person out
until April today, before the change, and after it, identically — three cycles paid, one
consultation, either way. The move to fixed cycles does not make it worse; it only makes
the loss *nameable* ("you lost the February cycle"), where before there was no cycle to
name.

Since nothing here needs containing, the cap leaves this ADR. The underlying problem is
real and pre-existing, and capping how far ahead a customer may book is a product decision
that needs its own story and PO sign-off rather than a ride inside a renewal epic. Tracked
in issue #252.

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

### D13 — A cancellation whose debited cycle has already closed refunds the quota by stamping the appointment *(Q12)*

> **Revised 2026-08-16, during implementation.** This decision replaces the original
> D13–D17, which routed the refund through the credit ledger. Those five decisions are
> reproduced at the end of this section for the record, together with the reason they
> were dropped.
>
> **Open with product — issue #275.** The revision settled *how* the refund is produced,
> not *whether* it should exist, and the epic points the other way: US-374 states that
> unused cycle credit expires at close, and the epic's own out-of-scope list says
> "reembolso de créditos expirados — não previsto". No story asks for this behaviour; it
> originated here, as Q12. It ships as described below, and the implementation was kept
> deliberately minimal so that removing it is one column and one term if product decides
> the consultation should simply be lost.

**The case.** Renewal on the 10th. The person books on **9 Sep** (last day of their
cycle) an appointment for **12 Sep**; the debit sits in the cycle that closed on the 9th.
On **11 Sep**, already inside the new cycle, they cancel with more than 4 hours' notice.
Removing a debit from a cycle nobody reads any more returns nothing: they followed the
rules and got nothing.

Note this is a **regression introduced by this change**. With the rolling window,
cancelling always frees the slot, because the window is relative to the read. It also
flips the sign: today cancelling in time is neutral or slightly advantageous, and after
the change it becomes a penalty.

**The decision.** At the moment of cancellation, when the status becomes `cancelled` and
the appointment was **not** paid with a `UserCredit` and the cycle containing its
`created_at` has already closed, stamp `appointments.quota_refunded_at = now()`.
`monthlyAppointmentsLeft()` then reads:

```
limit
  − appointments created inside the current cycle, status != cancelled
  + appointments whose quota_refunded_at falls inside the current cycle
```

**Why the stamp is written at cancellation and not derived on read.** The question "was
this appointment paid with quota or with a credit?" has an answer only at that instant.
Paying with quota never writes a row anywhere — the quota is derived, not stored — and
`ReturnCreditOnAppointmentCancelledListener` nulls `user_credits.appointment_id` when it
returns the credit. The listener runs synchronously, right after the status update, so
the stamp must be computed **before** the `AppointmentCreditReturned` event is fired.
Afterwards an appointment paid with quota and one paid with a credit are indistinguishable.

**What this buys.** The refund expires on its own: the stamp is an instant, and once the
cycle turns it no longer falls inside the window. No expiry column, no consumption
ordering, no "keeps its original expiry" rule, no `reason` enum, no screens.

**Accepted cost — auditability.** No admin-visible record is created. Support cannot open
a screen and read "refunded because the cycle had closed"; they have to cross-reference
the person's appointments. This weighs less than it appears: the admin panel does not
display anyone's quota today (`monthly_appointments_left` appears once in `panel-admin`,
inside `CreateAppointment`, and in no Blade), so a `CreditGrant` would audit the origin
of a number nobody can see. The evidence is preserved even if not presented —
`created_at`, `status` and `quota_refunded_at` stay on the appointment row forever. If it
ever matters, the cheap mitigation is one line in `PlanCreditsWidget` showing
"+1 returned by cancellation"; the full one is a column in the admin appointment list.
Tracked in issue #256.

**Rejected — refund nothing:** the person cancelled within the rules, and the sign flip
above makes it read as punishment for good behaviour.

*This rejection is the part now reopened in issue #275,* on an argument this ADR did not
weigh: someone who simply never books in a cycle loses it without discussion, so refunding
the canceller leaves them ahead of the no-shower, with neither having been seen. The
counter-argument is that the canceller was barred from rebooking inside the closed cycle by
the one-open-appointment rule, while the person who never booked had the month free.

**Rejected — issue a `UserCredit` with an expiry, recorded as a `CreditGrant` with a
`reason` (the original D13–D17).** It was chosen first and then dropped once implementation
made the trade explicit:

* it modelled the refund as an *avulso credit*, which it is not — it is the same monthly
  appointment restored. As a `UserCredit` it inherits avulso semantics (listed in "My
  Credits", transferable, revocable, counted as a donation), and each inherited property
  then has to be filtered back out. The six screens, the `reason` column and the revoke
  guard were all that undoing;
* it required D14 (expiry), D15 (consumption ordering) and D16 (keep the original expiry)
  to exist purely so a credit would behave like quota — and this ADR already conceded that
  D15 changes no outcome today and that D16 is unfair by design;
* it put the refund next to `ConsumeCredit`, whose `?->update(...)` no-ops silently, which
  is the most expensive class of bug available here (see §5.5 of the original text);
* the premise used to reject the arithmetic alternative was wrong. The original D13 stated
  that expressing "+1 in the current cycle" would require the materialised table rejected
  in D11. It does not: it requires knowing *when* the cancellation happened, which is one
  column.

**Rejected — refund into the plan quota by raising the limit:** the limit is what the
price sold and must not move.

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

**Confirmed 2026-08-16, with the size of the deviation worked out.** The new window is
narrower than the fixed 30 days in every case except the final day of a 31-day cycle, so
the common outcome is that a person *gains* an appointment at the deploy. Against them, the
cost is at most one extra day of waiting, and no paid consultation is ever erased.

The group that comes out worse is structurally tiny, and that is deducible rather than
something to go and measure: to be in it, a person must be on the last day of a 31-day
cycle **and** hold a booking made in a band of roughly one day. Anchors are spread across
the month, so this is a sliver of a sliver.

A read-only command comparing both rules per person was proposed and dropped for exactly
that reason — it would confirm what the arithmetic already guarantees. This paragraph is
the transition strategy the epic's Risk 1 asks FLM-375 to state; stating it is the
deliverable, not running something. The one genuine unknown is the size of the tables,
which is two `count(*)` queries at deploy time, not an artifact.

The two anchor backfills are not part of this decision and run regardless: `starts_at` on
`company_plans` and `quota_anchor_at` on `billing_subscriptions`. Both are irreversible by
design — once written there is no way to tell which rows were null — and the second reaches
only rows already `active`, so that a subscription still `pending` is anchored by the
observer at activation rather than frozen on a pre-payment date.

---

## 3. Worked examples (use these as test cases)

Renewal day 10 unless stated otherwise.

| # | Scenario | Expected |
| --- | --- | --- |
| 1 | Cycle 10 Aug–9 Sep. Books 9 Sep for 12 Sep. | Debit lands in the 10 Aug–9 Sep cycle. The 10 Sep cycle opens with full quota. |
| 2 | Same, then the appointment completes on 12 Sep. | Person may book again inside the 10 Sep–9 Oct cycle. Two appointments happen in September; two cycles were paid for. |
| 3 | Same, but cancelled on 11 Sep with >4h notice. | The debited cycle has closed → stamp `quota_refunded_at` and the 10 Sep–9 Oct cycle reads 2 available (D13). |
| 4 | Anchor 31 Jan. | Cycle starts: 28 Feb, 31 Mar, 30 Apr, 31 May (D2). |
| 5 | Employee registered on the 7th, company renews on the 10th. | Full quota; must **book** by the 9th; the appointment may occur after the 10th; unused → lost, never doubled (D8). |
| 6 | Renewal day 18. Books 7 Aug for 20 Aug, reschedules on 19 Aug to 5 Sep. | Debit stays in 18 Jul–17 Aug. The 18 Aug–17 Sep cycle is **not** debited; the person is merely blocked until 5 Sep, then may book again until 17 Sep. Never accumulates: on completion they have 1 available, not 3. |
| 7 | Person has an active contractual plan (1) and an active individual subscription (5). | Result is 1 (D6) — existing test must keep passing. |
| 8 | Person is an employee of two companies with active contractual plans. | The quota and the anchor come from the selected tenant (D7). |
| 9 | Admin raises `monthly_appointments_per_employee` from 1 to 2 on the 20th; the person used theirs on the 12th. | 1 available immediately, in the running cycle (D12). |
| 10 | Appointment paid with a `UserCredit` is cancelled in time after the turn. | No stamp: the existing listener returns the credit, and stamping too would double the benefit (D13). |
| 11 | A refund stamped on 11 Sep, read on 10 Oct. | Gone: the stamp no longer falls inside the current cycle, so the refund expires on its own (D13). |
| 12 | Admin cancels an appointment after the turn. | Refunded: admin cancellations are never penalised, so they resolve to `cancelled` and stamp (D9, D13). |
| 13 | Contractual plan with `starts_at` null (pre-backfill data). | Must not happen after the migration; the backfill sets `created_at` (D3). |
| 14 | Subscription still `pending` when the backfill runs, activated later. | Left unanchored by the backfill; the observer stamps it at activation (D4). |

---

## 4. Consequences

**Improves**

* The renewal date becomes a stable, explainable fact ("you renew on the 10th") aligned
  with the contract instead of drifting with usage.
* The company and all its employees share one renewal day, which is what the contractual
  product promises.
* The arbitrary `first()` company pick disappears, along with the latent default-company
  hazard, and the copy-pasted active-plan query gains a single owner.
* Cancelling in time keeps working after the turn, which the rolling window gave for free
  and a naive fixed cycle would have taken away.

**Regresses / costs**

* A refund leaves no admin-visible record. The evidence lives on the appointment row, but
  no screen presents it (D13, issue #256).
* `appointments` gains two columns whose meaning is only legible together with the cycle
  arithmetic: `quota_refunded_at` is a conclusion, not a raw fact, so a bug in the stamping
  rule is written into the data and would need a backfill to correct.
* The stamp must be computed before `AppointmentCreditReturned` fires. That ordering is
  load-bearing and easy to break by moving one line.

**Accepted risks**

* D12 means an admin edit rewrites the running cycle. Rare, admin-driven, one appointment.
* `monthly_appointments_left` is memoised per model instance (`shouldCache()`), and nothing
  but re-hydrating the model clears that. Reading it, mutating an appointment and reading it
  again **on the same instance** returns the stale value. No current call site does that —
  each reads once, and mutation comes after — but the pattern is the one to watch.
* D18 lets one cycle straddle the cutover. Bounded at one extra day of waiting, and
  measured before the deploy rather than assumed.

---

## 5. Implementation map

### 5.1 Phasing

Shipped as one phase. The original text split it in two — anchor first, refund later — to
keep the credit-ledger work off the critical path. Once the refund became one column and
one arithmetic term (D13), the split lost its reason to exist, and with it the interim
regression it would have imposed.

### 5.2 Schema changes

| Table | Change |
| --- | --- |
| `company_plans` | No schema change. `starts_at` stays a nullable `date` in the DB; the requirement is enforced in the admin form. Separate backfill migration, done in PHP in chunks rather than `DATE(created_at)`, so it does not depend on a date function that differs between pgsql and sqlite. |
| `billing_subscriptions` | Add `quota_anchor_at` (nullable `timestamp`). Nullable because a subscription may exist before it is ever activated. Backfilled from `created_at` by a **separate** migration, and only for rows already `active`. |
| `appointments` | Add `quota_refunded_at` (nullable `timestamp`). Stamped at cancellation when the debited cycle has closed and the appointment was not paid with a credit. |

Schema and data migrations are kept apart on purpose: a migration that both alters a table
and writes rows cannot be re-run in a test, so its backfill ships unexercised.

### 5.3 New code

* **`QuotaCycle`** — `app-modules/billing/src/Core/Support/`. Given an anchor and "now",
  returns the current period's `start` (inclusive) and `end` (exclusive), plus `contains()`
  and `hasClosed()`. Single home of D2's clamping. Step from the **original** anchor
  (`addMonthsNoOverflow($n)`), never from the previously computed start, or "no drift" is
  violated. Note `Illuminate\Support\CarbonImmutable` does not exist in this version —
  the class is `Carbon\CarbonImmutable`.
* **`QuotaAllowance`** — `app-modules/billing/src/Core/DTOs/`. Carries `limit` and `anchor`
  together, because resolving one without the other leaves the door open to mixing sources.
* **`ResolveQuotaAllowance`** — `app-modules/billing/src/Core/Actions/`. Company from the
  Filament tenant, falling back to `employerCompanyId()`; contractual plan takes precedence
  over the user's own subscription. Also exposes `contractualPlanFor()`, so any screen that
  *describes* the plan describes the same one the balance came from.
* **`CompanyPlan::active()`** — an Eloquent scope, the single owner of the query that was
  copy-pasted in four places (1.4.13).
* **`SubscriptionQuotaAnchorObserver`** — stamps `quota_anchor_at` on `saving`, the only
  point every write path passes through (D4).

### 5.4 Files to touch

**Quota calculation (D1, D2, D5, D6, D7, D8, D11, D12)**
* `app/Models/Users/User.php` — `monthlyAppointmentsLeft()` counts inside the cycle and adds
  `quotaRefundsInCycle()`; `resolveMonthlyAppointmentLimit()` is deleted, its job having
  moved to `ResolveQuotaAllowance`.

**Cross-request cache removed**

The attribute used to sit behind a one-minute `Cache::remember`. It was dropped, together
with `forgetMonthlyAppointmentsLeftCache()` and its call in the cancellation transition.

The cache bought a handful of cheap queries per request — the attribute is read in exactly
four places, all single-user, none in a loop — and cost a real defect: panel-admin's
`CreateAppointment` does not check `hasOngoingAppointment()`, so two appointments created for
the same person inside the same minute both read a full quota, and the second one skips
consuming a credit. Per-instance memoisation via `shouldCache()` stays; see the residual risk
in §4.

**Contractual anchor (D3)**
* `.../Companies/RelationManagers/ContractualPlansRelationManager.php` — `starts_at` becomes
  `->required()`. The `?? now()` fallback in the overlap rule stays: that validation runs
  before the required check and would receive `null`.
* Backfill migration.

**Individual anchor (D4)**
* Migration for `quota_anchor_at`, plus a separate backfill migration.
* `.../Models/Subscriptions/Subscription.php` — property docblock, `STATUS_ACTIVE`, a
  `casts()` **method** (merged with Cashier's `$casts` property; overriding the property
  would drop `ends_at` and `trial_ends_at`), and `#[ObservedBy]`.
* `UpsertSubscription` is **not** touched — see D4 on why the stamp lives in the observer.

**Refund (D13)**
* `.../Transitions/AbstractAppointmentTransition.php` — `stampQuotaRefundIfCycleClosed()`,
  called from `cancelProcessStep()` **before** `AppointmentCreditReturned` is fired. The
  ordering is load-bearing: that listener nulls `user_credits.appointment_id`, and after it
  runs there is no way left to tell whether the appointment was paid with a credit.
* `.../Models/Appointment.php` — property docblock and cast for `quota_refunded_at`. Not
  fillable: it is system-set.

**Screen (closes the problem statement)**
* `.../Widgets/PlanCreditsWidget.php` and `resources/views/filament/app/widgets/plan-credits.blade.php`
  — show the renewal date, derived from `QuotaCycle::forAnchor($allowance->anchor)->end`.
  Until now this was impossible to state honestly, because a rolling window has no renewal
  date to state.

### 5.5 Ordering that must not be broken

The stamp in `cancelProcessStep()` has to run **before** `event(new AppointmentCreditReturned(...))`.
That listener is synchronous and sets `user_credits.appointment_id` to null; paying with
quota never wrote a row anywhere. Move the stamp after the event and an appointment paid
with a credit gets its credit back *and* a quota refund — the benefit doubles, silently.

The original text warned about a different pitfall here — the expiry filter having to reach
`ConsumeCredit`, `hasAvailableCredit()`, `PlanCreditsWidget` and `UserCreditsPage`, on pain
of a silent no-op debit. That pitfall no longer exists: nothing in this change touches the
credit ledger.

### 5.6 Tests

Written for this change:

* `app-modules/billing/tests/Unit/QuotaCycleTest.php` — cycle, clamp without drift, the
  turn instant, a future anchor, `contains()`, `hasClosed()`.
* `tests/Feature/UserMonthlyAppointmentsLeftTest.php` — previous-cycle booking does not
  count, current-cycle booking does, quota never accumulates, `cancelled` vs
  `cancelled_late`, mid-cycle joiner, no plan.
* `app-modules/appointments/tests/Feature/Actions/QuotaRefundOnClosedCycleTest.php` — the
  six cases of D13, including the credit-paid appointment that must **not** be refunded and
  the refund expiring on its own at the next turn.
* `app-modules/billing/tests/Feature/Subscription/QuotaAnchorTest.php` — stamping on first
  activation through both write paths, never moving afterwards, and the two backfill rules.
* `app-modules/billing/tests/Feature/CompanyPlan/BackfillStartsAtTest.php` and the backfill
  cases in `QuotaAnchorTest` — both execute the migration itself against hand-made legacy
  rows. The test database starts clean, so without this the backfills would reach production
  never having run.
* `app-modules/panel-admin/tests/Feature/Filament/ContractualPlansRelationManagerTest.php`
  — the `starts_at` cases live alongside the contract-value ones the financial cockpit
  added, rather than in a second file with the same name.

Pre-existing tests that constrain the change:

* `app-modules/appointments/tests/Feature/Actions/BookAppointmentActionCreditTest.php` and
  `.../CreateAppointmentCreditTest.php` — both rely on "a user with no company/plan always
  has `monthly_appointments_left = 0`", which must remain true.
* `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php` —
  asserts `canCreateAppointment` and the block reasons share one computation.

Interactions with what landed on develop while this was being built:

* a no-show consumes the cycle and never refunds (`UserMonthlyAppointmentsLeftTest`);
* the anchor is stamped through both write paths, Barte/Virtu via `UpsertSubscription` and
  Stripe via the billable relation (`QuotaAnchorTest`).

---

## 6. Deliberately out of scope

Recorded so they are not mistaken for oversights. Each of the first four has an issue, so
that closing the epic does not close the question.

1. A company's own subscription granting quota to employees (D5) — issue #255. The epic's
   STORY-373 describes this as existing behaviour; it never has.
2. A cap on how far ahead a person may book (D10, withdrawn) — issue #252.
3. An auditable cycle history, which STORY-374 lists as a Must Have and the derived design
   does not produce (D11) — issue #253.
4. The renewal e-mail of STORY-376, which has no trigger once the renewal job is gone
   (D11) — issue #254. Its approved copy also promises a 6-hour cancellation window; the
   system uses 4.
5. A limit on the number of reschedules per appointment.
6. Whether the default company should appear in the tenant switcher (D7's residue).
7. Summing contractual and individual quota for the same person (D6).
8. Unifying the duplicated "which stock pays" decision between `BookAppointmentAction` and
   panel-admin's `CreateAppointment` (1.4.10). The refund no longer depends on it, but the
   divergence is real and pre-existing.
