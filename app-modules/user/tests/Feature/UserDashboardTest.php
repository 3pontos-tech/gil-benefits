<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use TresPontosTech\Billing\Stripe\Subscription\User\RedirectUserIfNotSubscribed;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Filament\Pages\UserDashboard;
use TresPontosTech\PanelApp\Filament\Widgets\JourneyHeroWidget;
use TresPontosTech\PanelApp\Filament\Widgets\LatestAppointmentsWidget;
use TresPontosTech\PanelApp\Filament\Widgets\PlanCreditsWidget;
use TresPontosTech\PanelApp\Filament\Widgets\SharedMaterialsWidget;

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

it('should have the hub widgets', function (): void {
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
        ->assertSeeLivewire(JourneyHeroWidget::class)
        ->assertSeeLivewire(LatestAppointmentsWidget::class)
        ->assertSeeLivewire(PlanCreditsWidget::class)
        ->assertSeeLivewire(SharedMaterialsWidget::class);
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
