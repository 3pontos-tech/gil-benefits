<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use TresPontosTech\App\Filament\Pages\UserDashboard;
use TresPontosTech\App\Filament\Widgets\AppointmentHistoryWidget;
use TresPontosTech\App\Filament\Widgets\LatestAppointmentWidget;
use TresPontosTech\App\Filament\Widgets\UserCurrentPlanWidget;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Stripe\Subscription\User\RedirectUserIfNotSubscribed;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = User::factory()->employee()->create();
    actingAs($this->employee);
    $company = Company::factory()->createOne();
    $company->employees()->attach($this->employee);
    $this->tenant = $company;
    filament()->setTenant($this->tenant);
    filament()->setCurrentPanel(FilamentPanel::User->value);
});

it('should render', function (): void {
    livewire(UserDashboard::class)
        ->assertOk();
});
it('should have some widgets', function (): void {
    app()->instance(RedirectUserIfNotSubscribed::class,
        new class
        {
            public function handle(Request $request, Closure $next)
            {
                return $next($request);
            }
        });
    $this->get(route('filament.app.pages.user-dashboard', ['tenant' => filament()->getTenant()->slug]))
        ->assertOk()
        ->assertSeeLivewire(UserCurrentPlanWidget::class)
        ->assertSeeLivewire(LatestAppointmentWidget::class)
        ->assertSeeLivewire(AppointmentHistoryWidget::class);
});

it('should receive forbidden if tenant is not subscribed in any plan', function (): void {
    app()->instance(RedirectUserIfNotSubscribed::class,
        new class
        {
            public function handle(Request $request, Closure $next)
            {
                /** @var Company $tenant */
                $tenant = Filament::getTenant();
                $hasActiveSubscription = $tenant
                    ->subscriptions()
                    ->whereIn('stripe_status', ['active', 'incomplete'])
                    ->exists();

                abort_unless($hasActiveSubscription, 403);

                return $next($request);
            }
        });

    $this->get(route('filament.app.pages.user-dashboard', ['tenant' => filament()->getTenant()->slug]))
        ->assertForbidden();

    $this->tenant->subscriptions()->create([
        'type' => 'User',
        'stripe_status' => 'active',
        'stripe_id' => 'plan' . uniqid(),
    ]);
    $this->get(route('filament.app.pages.user-dashboard', ['tenant' => filament()->getTenant()->slug]))
        ->assertOk();
});

describe('testing widgets that are on user dashboard', function (): void {

    test('latest appointment', function (): void {
        Appointment::factory()->for($this->employee, 'user')->count(4)->create(['created_at' => now()->subMinutes(5)]);
        $latest = Appointment::factory()->for($this->employee, 'user')->withStatus(AppointmentStatus::Pending)->create();

        livewire(LatestAppointmentWidget::class)
            ->assertOk()
            ->assertSeeText('Próxima consultoria')
            ->assertSeeText($latest->status->getLabel());
    });
    test('latest appointment, zero appointments', function (): void {
        livewire(LatestAppointmentWidget::class)
            ->assertOk()
            ->assertSeeText('Nenhum agendamento encontrado.');
    });
    test('appointment history', function (): void {

        $appointments = Appointment::factory()->for($this->employee, 'user')->count(5)->create();
        livewire(AppointmentHistoryWidget::class)
            ->assertOk()
            ->assertCanSeeTableRecords($appointments)
            ->assertSeeText($appointments->first()->status->getLabel());
    });
});
