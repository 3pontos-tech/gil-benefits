<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Database\Seeders;

use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Enums\DepartmentCategory;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\Roles;

/**
 * Fills the standard dev company (slug "5pontos", login company@5pontos.com)
 * with enough activity for the Command Dashboard to render "full" — no separate
 * demo tenant or login required. Idempotent and non-destructive: demo employees
 * are tagged with the "@5pontos.demo" e-mail domain, so re-running wipes only
 * what this seeder produced and leaves the EssentialsSeeder setup (owner, the
 * canonical employee, the Ata-feature appointments, documents, plan) intact.
 * Skipped in production.
 *
 * Access: /company → company@5pontos.com / password
 */
class CommandDashboardDemoSeeder extends Seeder
{
    private const OWNER_EMAIL = 'company@5pontos.com';

    private const COMPANY_SLUG = '5pontos';

    /** Marker domain for the synthetic employees this seeder owns. */
    private const DEMO_EMAIL_DOMAIN = '5pontos.demo';

    /** Total appointments per month over the last 12 months (oldest → newest). */
    private const MONTHLY_VOLUME = [88, 104, 120, 112, 138, 156, 172, 165, 192, 210, 232, 224];

    /** Credits purchased that are still sitting available in the company wallet. */
    private const AVAILABLE_CREDITS = 420;

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $company = $this->resolveCompany();

        /** @var User $owner */
        $owner = $company->owner;

        $this->cleanup($company);

        $departments = $this->seedDepartments($company);
        $employees = $this->seedEmployees($company, $departments);
        $this->alignMembershipDates($company);

        $withPlan = $employees->where('hasPlan', true);

        $this->ensureActivePlan($company, max(1, $withPlan->count()));

        $bookingPool = $this->expandPool(
            $withPlan->map(fn (array $entry): array => [
                'id' => (string) $entry['user']->getKey(),
                'weight' => $entry['weight'],
            ])->all(),
        );

        $this->seedAppointments($company, $bookingPool, $this->consultantPool());
        $this->seedCredits($company, $owner, $employees->pluck('user'));

        $this->command?->info(sprintf('Empresa %s populada → /company · login: ', $company->slug) . self::OWNER_EMAIL . ' / password');
    }

    /**
     * The standard dev company. Falls back to creating it (owner + membership)
     * when seeding a fresh database that has not run EssentialsSeeder yet.
     */
    private function resolveCompany(): Company
    {
        $company = Company::query()->where('slug', self::COMPANY_SLUG)->first();

        if ($company instanceof Company) {
            return $company;
        }

        $owner = User::factory()->companyOwner()->create([
            'name' => 'Company Owner',
            'email' => self::OWNER_EMAIL,
            'password' => Hash::make('password'),
        ]);

        $owner->assignRole(Roles::CompanyOwner->value);

        $company = Company::factory()->recycle($owner)->create([
            'name' => '5Pontos',
            'slug' => self::COMPANY_SLUG,
        ]);

        $company->employees()->attach($owner->getKey(), [
            'role' => Roles::CompanyOwner->value,
            'active' => true,
        ]);

        return $company;
    }

    /**
     * Removes everything this seeder previously generated for the company: the
     * synthetic employees (by demo e-mail domain), their appointments/feedback
     * and the company's credits and departments. EssentialsSeeder data survives.
     */
    private function cleanup(Company $company): void
    {
        DB::table('company_employees')
            ->where('company_id', $company->getKey())
            ->update(['department_id' => null]);

        // Hard-delete: departments use soft deletes, but the (company_id, name)
        // unique index still counts soft-deleted rows, which would collide on
        // re-seed.
        DB::table('departments')->where('company_id', $company->getKey())->delete();

        // EssentialsSeeder creates no credits for this company, so every credit
        // here belongs to a previous demo run.
        DB::table('user_credits')->where('company_id', $company->getKey())->delete();

        $demoUserIds = User::query()
            ->where('email', 'like', '%@' . self::DEMO_EMAIL_DOMAIN)
            ->pluck('id')
            ->all();

        if ($demoUserIds === []) {
            return;
        }

        $demoAppointmentIds = DB::table('appointments')
            ->where('company_id', $company->getKey())
            ->where(function ($query) use ($demoUserIds): void {
                $query->whereIn('user_id', $demoUserIds)
                    ->orWhereIn('cancelled_by', $demoUserIds);
            })
            ->pluck('id')
            ->all();

        // Hard-delete at the DB layer: these models use soft deletes, and a
        // soft-deleted row keeps its foreign key alive — which would block the
        // removal of the demo users below.
        DB::table('appointment_feedbacks')->whereIn('user_id', $demoUserIds)->delete();
        DB::table('appointment_feedbacks')->whereIn('appointment_id', $demoAppointmentIds)->delete();
        DB::table('user_credits')->whereIn('holder_id', $demoUserIds)->delete();
        DB::table('appointments')->whereIn('id', $demoAppointmentIds)->delete();
        DB::table('user_details')->whereIn('user_id', $demoUserIds)->delete();
        DB::table('user_anamneses')->whereIn('user_id', $demoUserIds)->delete();
        DB::table('company_employees')->whereIn('user_id', $demoUserIds)->delete();
        DB::table('users')->whereIn('id', $demoUserIds)->delete();
    }

    private function ensureActivePlan(Company $company, int $seats): void
    {
        if ($company->hasActivePlan()) {
            return;
        }

        CompanyPlan::factory()->active()->for($company)->create([
            'seats' => $seats,
            'monthly_appointments_per_employee' => 2,
        ]);
    }

    /**
     * @return Collection<int, array{department: Department, adoption: float, headcount: int}>
     */
    private function seedDepartments(Company $company): Collection
    {
        return collect($this->departmentBlueprint())->map(fn (array $definition): array => [
            'department' => Department::factory()->create([
                'company_id' => $company->getKey(),
                'name' => $definition['name'],
                'category' => $definition['category'],
            ]),
            'adoption' => $definition['adoption'],
            'headcount' => $definition['headcount'],
        ]);
    }

    /**
     * Department layout: headcount drives the funnel size, adoption drives the
     * share of each department that actually books sessions (so the adoption
     * bars vary instead of all landing on the same percentage). Headcounts sum
     * to 150 employees.
     *
     * @return array<int, array{name: string, category: DepartmentCategory, headcount: int, adoption: float}>
     */
    private function departmentBlueprint(): array
    {
        return [
            ['name' => 'Operações', 'category' => DepartmentCategory::Operations, 'headcount' => 28, 'adoption' => 0.50],
            ['name' => 'Comercial', 'category' => DepartmentCategory::Commercial, 'headcount' => 24, 'adoption' => 0.78],
            ['name' => 'TI', 'category' => DepartmentCategory::Technology, 'headcount' => 18, 'adoption' => 0.85],
            ['name' => 'Financeiro', 'category' => DepartmentCategory::Finance, 'headcount' => 16, 'adoption' => 0.70],
            ['name' => 'Customer Success', 'category' => DepartmentCategory::CustomerSuccess, 'headcount' => 15, 'adoption' => 0.74],
            ['name' => 'Marketing', 'category' => DepartmentCategory::Marketing, 'headcount' => 13, 'adoption' => 0.64],
            ['name' => 'RH', 'category' => DepartmentCategory::HumanResources, 'headcount' => 12, 'adoption' => 0.92],
            ['name' => 'Administrativo', 'category' => DepartmentCategory::Administrative, 'headcount' => 10, 'adoption' => 0.46],
            ['name' => 'Jurídico', 'category' => DepartmentCategory::Legal, 'headcount' => 8, 'adoption' => 0.56],
            ['name' => 'Diretoria', 'category' => DepartmentCategory::Executive, 'headcount' => 6, 'adoption' => 0.88],
        ];
    }

    /**
     * @param  Collection<int, array{department: Department, adoption: float, headcount: int}>  $departments
     * @return Collection<int, array{user: User, hasPlan: bool, weight: int}>
     */
    private function seedEmployees(Company $company, Collection $departments): Collection
    {
        $employees = collect();
        $index = 0;

        foreach ($departments as $entry) {
            $department = $entry['department'];

            for ($i = 0; $i < $entry['headcount']; ++$i) {
                ++$index;
                $hasAccess = rand(1, 100) <= 87;             // ~87% activated their account
                $hasPlan = $hasAccess && rand(1, 100) <= 78; // ~68% overall adoption
                $isNewThisMonth = rand(1, 100) <= 11;

                $user = User::factory()->employee()->create([
                    'email' => sprintf('colaborador-%03d@%s', $index, self::DEMO_EMAIL_DOMAIN),
                    'email_verified_at' => $hasAccess ? now() : null,
                    'created_at' => $this->joinedAt($isNewThisMonth),
                ]);

                $company->employees()->attach($user->getKey(), [
                    'role' => Roles::Employee->value,
                    'active' => true,
                    'department_id' => $department->getKey(),
                ]);

                if ($hasPlan) {
                    $user->subscriptions()->create([
                        'type' => 'default',
                        'stripe_id' => 'demo_sub_' . Str::random(10),
                        'stripe_status' => 'active',
                        'quantity' => 1,
                    ]);
                }

                $employees->push([
                    'user' => $user,
                    'hasPlan' => $hasPlan,
                    'weight' => $this->bookingWeight($hasPlan, $entry['adoption']),
                ]);
            }
        }

        return $employees;
    }

    /**
     * Reuses the consultants already in the database, weighted so a clear top-5
     * emerges in the ranking instead of an even split.
     *
     * @return array<int, string>
     */
    private function consultantPool(): array
    {
        $consultants = Consultant::query()->orderBy('id')->limit(12)->get();

        $entries = $consultants->values()->map(fn (Consultant $consultant, int $index): array => [
            'id' => (string) $consultant->getKey(),
            'weight' => max(2, 12 - $index),
        ])->all();

        return $this->expandPool($entries);
    }

    /**
     * @param  array<int, string>  $bookingPool
     * @param  array<int, string>  $consultantPool
     */
    private function seedAppointments(Company $company, array $bookingPool, array $consultantPool): void
    {
        if ($bookingPool === [] || $consultantPool === []) {
            return;
        }

        DB::transaction(function () use ($company, $bookingPool, $consultantPool): void {
            $monthCount = count(self::MONTHLY_VOLUME);

            foreach (self::MONTHLY_VOLUME as $offset => $total) {
                $month = now()->subMonthsNoOverflow($monthCount - 1 - $offset)->startOfMonth();
                $isCurrentMonth = $offset === $monthCount - 1;

                for ($i = 0; $i < $total; ++$i) {
                    $status = $this->pickStatus($isCurrentMonth);

                    $appointment = Appointment::factory()->create([
                        'company_id' => $company->getKey(),
                        'consultant_id' => Arr::random($consultantPool),
                        'user_id' => Arr::random($bookingPool),
                        'status' => $status,
                        'category_type' => $this->pickCategory(),
                        'appointment_at' => $this->randomDateIn($month),
                    ]);

                    if ($status === AppointmentStatus::Completed && rand(1, 100) <= 72) {
                        AppointmentFeedback::factory()->create([
                            'appointment_id' => $appointment->getKey(),
                            'user_id' => $appointment->user_id,
                            'rating' => $this->pickRating(),
                        ]);
                    }
                }
            }
        });
    }

    /**
     * @param  Collection<int, User>  $employees
     */
    private function seedCredits(Company $company, User $owner, Collection $employees): void
    {
        DB::transaction(function () use ($company, $owner, $employees): void {
            UserCredit::factory()->count(self::AVAILABLE_CREDITS)->available()->create([
                'company_id' => $company->getKey(),
                'owner_id' => $owner->getKey(),
                'holder_id' => $owner->getKey(),
            ]);

            $completed = Appointment::query()
                ->where('company_id', $company->getKey())
                ->where('status', AppointmentStatus::Completed->value)
                ->get(['id', 'user_id', 'appointment_at']);

            UserCredit::factory()->count(max(1, (int) round($completed->count() / 6)))->inUse()->create([
                'company_id' => $company->getKey(),
                'owner_id' => $owner->getKey(),
                'holder_id' => $employees->random()->getKey(),
                'transferred_at' => now()->subDays(rand(1, 20)),
            ]);

            $completed->each(function (Appointment $appointment) use ($company, $owner): void {
                UserCredit::factory()->used()->create([
                    'company_id' => $company->getKey(),
                    'owner_id' => $owner->getKey(),
                    'holder_id' => $appointment->user_id,
                    'appointment_id' => $appointment->getKey(),
                    'transferred_at' => $appointment->appointment_at,
                ]);
            });
        });
    }

    /**
     * Mirror each membership's pivot created_at onto the user's join date so the
     * "new this month" funnel figure reflects the seeded dates — attach() always
     * stamps the pivot with the current time, which would mark everyone as new.
     */
    private function alignMembershipDates(Company $company): void
    {
        DB::statement(
            'UPDATE company_employees SET created_at = users.created_at FROM users WHERE company_employees.user_id = users.id AND company_employees.company_id = ?',
            [$company->getKey()],
        );
    }

    /**
     * Expand weighted entries into a flat pool so Arr::random() picks
     * proportionally to weight. A weight of zero drops the entry entirely.
     *
     * @param  array<int, array{id: string, weight: int}>  $entries
     * @return array<int, string>
     */
    private function expandPool(array $entries): array
    {
        $pool = [];

        foreach ($entries as $entry) {
            for ($i = 0; $i < $entry['weight']; ++$i) {
                $pool[] = $entry['id'];
            }
        }

        return $pool;
    }

    /**
     * How many slots an employee gets in the booking pool. Zero means they have
     * a plan but never booked — which keeps department adoption rates varied.
     */
    private function bookingWeight(bool $hasPlan, float $adoption): int
    {
        if (! $hasPlan || rand(1, 100) > $adoption * 100) {
            return 0;
        }

        return rand(1, 6);
    }

    private function joinedAt(bool $isNewThisMonth): Carbon
    {
        if ($isNewThisMonth) {
            return now()->startOfMonth()
                ->addDays(rand(0, max(0, now()->day - 1)))
                ->setTime(rand(8, 18), 0);
        }

        return now()->subMonthsNoOverflow(rand(2, 11))->subDays(rand(0, 27));
    }

    private function pickStatus(bool $isCurrentMonth): AppointmentStatus
    {
        $roll = rand(1, 100);

        if ($isCurrentMonth) {
            return match (true) {
                $roll <= 45 => AppointmentStatus::Completed,
                $roll <= 70 => AppointmentStatus::Active,
                $roll <= 90 => AppointmentStatus::Pending,
                $roll <= 96 => AppointmentStatus::Cancelled,
                default => AppointmentStatus::CancelledLate,
            };
        }

        return match (true) {
            $roll <= 84 => AppointmentStatus::Completed,
            $roll <= 90 => AppointmentStatus::Active,
            $roll <= 94 => AppointmentStatus::Pending,
            $roll <= 98 => AppointmentStatus::Cancelled,
            default => AppointmentStatus::CancelledLate,
        };
    }

    private function pickCategory(): AppointmentCategoryEnum
    {
        $weighted = [
            ...array_fill(0, 30, AppointmentCategoryEnum::PersonalFinance),
            ...array_fill(0, 22, AppointmentCategoryEnum::FundraisingAndCredit),
            ...array_fill(0, 18, AppointmentCategoryEnum::InvestmentAdvisory),
            ...array_fill(0, 12, AppointmentCategoryEnum::RetirementAndEstatePlanning),
            ...array_fill(0, 10, AppointmentCategoryEnum::RiskAndCompliance),
            ...array_fill(0, 8, AppointmentCategoryEnum::MergersAndAcquisitions),
        ];

        return Arr::random($weighted);
    }

    private function pickRating(): int
    {
        return match (true) {
            ($roll = rand(1, 100)) <= 55 => 5,
            $roll <= 85 => 4,
            $roll <= 95 => 3,
            $roll <= 98 => 2,
            default => 1,
        };
    }

    private function randomDateIn(Carbon $month): Carbon
    {
        $maxDay = $month->isCurrentMonth()
            ? now()->day
            : min($month->daysInMonth, 28);

        $day = rand(1, max(1, $maxDay));

        return $month->copy()->setDay($day)->setTime(rand(8, 18), Arr::random([0, 15, 30, 45]));
    }
}
