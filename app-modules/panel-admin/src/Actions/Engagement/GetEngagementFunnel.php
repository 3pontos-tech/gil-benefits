<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Engagement;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Engagement\Concerns\BuildsEngagementCacheKey;
use TresPontosTech\PanelAdmin\DTOs\EngagementFilters;
use TresPontosTech\PanelAdmin\DTOs\EngagementFunnelRow;

/**
 * Builds the engagement funnel of every company in scope: contracted seats,
 * registered beneficiaries, beneficiaries who booked, who actually had the
 * consultancy and who came back for more than one.
 *
 * Seats and registrations are cumulative up to the end of the period (a
 * beneficiary registered earlier still occupies a seat), while the booking and
 * completion steps only count appointments held inside the period.
 */
final class GetEngagementFunnel
{
    use BuildsEngagementCacheKey;

    /** Same status the company panel treats as a paying subscription. */
    private const string ACTIVE_SUBSCRIPTION_STATUS = 'active';

    /**
     * @return Collection<int, EngagementFunnelRow>
     */
    public function handle(EngagementFilters $filters): Collection
    {
        return Cache::remember(
            $this->engagementCacheKey('funnel', $filters),
            $this->engagementCacheTtl(),
            fn (): Collection => $this->build($filters),
        );
    }

    /**
     * @return Collection<int, EngagementFunnelRow>
     */
    private function build(EngagementFilters $filters): Collection
    {
        $companies = Company::query()
            ->when(
                $filters->companyIds !== [],
                fn (Builder $query) => $query->whereKey($filters->companyIds),
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($companies->isEmpty()) {
            return collect();
        }

        /** @var array<int, string> $companyIds */
        $companyIds = $companies->pluck('id')->all();

        $seats = $this->seatsByCompany($companyIds);
        $registered = $this->registeredByCompany($companyIds, $filters);
        $appointments = $this->appointmentStepsByCompany($companyIds, $filters);
        $recurrence = $this->recurrenceByCompany($companyIds, $filters);

        return $companies
            ->map(fn (Company $company): EngagementFunnelRow => new EngagementFunnelRow(
                companyId: $company->id,
                companyName: $company->name,
                seats: (int) ($seats[$company->id] ?? 0),
                registered: (int) ($registered[$company->id] ?? 0),
                withAppointment: (int) data_get($appointments, $company->id . '.with_appointment', 0),
                withCompletedAppointment: (int) data_get($appointments, $company->id . '.with_completed', 0),
                withRecurrence: (int) ($recurrence[$company->id] ?? 0),
            ))
            ->values();
    }

    /**
     * Seats available to each company: the active contractual plan when there is
     * one, otherwise the active platform subscription. Mirrors the precedence of
     * the seat counter the company itself sees in its own panel, so the two
     * screens never disagree on the same number.
     *
     * @param  array<int, string>  $companyIds
     * @return array<string, int>
     */
    private function seatsByCompany(array $companyIds): array
    {
        $contractual = $this->contractedSeatsByCompany($companyIds);
        $subscribed = $this->subscribedSeatsByCompany($companyIds);

        return collect($companyIds)
            ->mapWithKeys(fn (string $companyId): array => [
                $companyId => $contractual[$companyId] ?? $subscribed[$companyId] ?? 0,
            ])
            ->all();
    }

    /**
     * Seats negotiated in the active contractual plans of each company.
     *
     * @param  array<int, string>  $companyIds
     * @return array<string, int>
     */
    private function contractedSeatsByCompany(array $companyIds): array
    {
        return CompanyPlan::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', CompanyPlanStatusEnum::Active)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->groupBy('company_id')
            ->selectRaw('company_id, SUM(seats) as total')
            ->pluck('total', 'company_id')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    /**
     * Seats bought by each company through the platform, for the companies that
     * pay a subscription instead of a negotiated contract.
     *
     * The shared default company is left out on purpose: its company-level
     * subscription is a synthetic "unlimited" record created by
     * app:sync-subscription-to-flamma-company — a random UUID as the Stripe id
     * and a hardcoded quantity — not contracted capacity. Its members pay
     * individually, and counting it would swamp the consolidated registration
     * rate. The company itself stays in the report, with no seats.
     *
     * @param  array<int, string>  $companyIds
     * @return array<string, int>
     */
    private function subscribedSeatsByCompany(array $companyIds): array
    {
        return Subscription::query()
            ->whereMorphedTo('owner', Company::class)
            ->whereIn('subscriptionable_id', $companyIds)
            ->whereNotIn(
                'subscriptionable_id',
                Company::query()->where('slug', Company::DEFAULT_SLUG)->select('id'),
            )
            ->where('stripe_status', self::ACTIVE_SUBSCRIPTION_STATUS)
            ->whereNotNull('quantity')
            ->groupBy('subscriptionable_id')
            ->selectRaw('subscriptionable_id, SUM(quantity) as total')
            ->pluck('total', 'subscriptionable_id')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    /**
     * Active beneficiaries (company owners excluded) registered up to the end
     * of the analysed period.
     *
     * @param  array<int, string>  $companyIds
     * @return array<string, int>
     */
    private function registeredByCompany(array $companyIds, EngagementFilters $filters): array
    {
        return DB::table('company_employees')
            ->join('companies', 'companies.id', '=', 'company_employees.company_id')
            ->whereIn('company_employees.company_id', $companyIds)
            ->where('company_employees.active', true)
            ->whereNull('company_employees.deleted_at')
            ->whereColumn('company_employees.user_id', '!=', 'companies.user_id')
            ->where('company_employees.created_at', '<=', $filters->end)
            ->groupBy('company_employees.company_id')
            ->selectRaw('company_employees.company_id as company_id, COUNT(*) as total')
            ->pluck('total', 'company_id')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    /**
     * Distinct beneficiaries with at least one appointment and with at least one
     * completed appointment inside the period, per company.
     *
     * @param  array<int, string>  $companyIds
     * @return array<string, array{with_appointment: int, with_completed: int}>
     */
    private function appointmentStepsByCompany(array $companyIds, EngagementFilters $filters): array
    {
        return $this->periodAppointments($companyIds, $filters)
            ->groupBy('company_id')
            ->selectRaw('company_id')
            ->selectRaw('COUNT(DISTINCT user_id) as with_appointment')
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN status = ? THEN user_id END) as with_completed',
                [AppointmentStatus::Completed->value],
            )
            ->toBase()
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (string) $row->company_id => [
                    'with_appointment' => (int) $row->with_appointment,
                    'with_completed' => (int) $row->with_completed,
                ],
            ])
            ->all();
    }

    /**
     * Beneficiaries with more than one completed consultancy inside the period,
     * per company.
     *
     * @param  array<int, string>  $companyIds
     * @return array<string, int>
     */
    private function recurrenceByCompany(array $companyIds, EngagementFilters $filters): array
    {
        return $this->periodAppointments($companyIds, $filters)
            ->where('status', AppointmentStatus::Completed)
            ->groupBy('company_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->selectRaw('company_id, user_id')
            ->toBase()
            ->get()
            ->groupBy('company_id')
            ->map(fn (Collection $users): int => $users->count())
            ->all();
    }

    /**
     * @param  array<int, string>  $companyIds
     * @return Builder<Appointment>
     */
    private function periodAppointments(array $companyIds, EngagementFilters $filters): Builder
    {
        return Appointment::query()
            ->whereIn('company_id', $companyIds)
            ->whereBetween('appointment_at', [$filters->start, $filters->end]);
    }
}
