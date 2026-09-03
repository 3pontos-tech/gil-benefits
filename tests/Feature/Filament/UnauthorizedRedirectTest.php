<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Billing\Core\Http\Middleware\RedirectUserIfNotSubscribed;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Http\Middleware\RedirectIfAnamneseNotCompleted;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * ÉPICO 56 — Redirecionamento por Perfil ao Acessar Página sem Autorização.
 * STORY 317 (redirecionar para a home do perfil) + STORY 318 (preservar erro
 * para não autenticados / perfis não mapeados).
 */
$adminDashboard = 'filament.admin.pages.dashboard';
$userDashboard = 'filament.app.pages.user-dashboard';

$makeEmployee = function (?Company &$company = null): User {
    $employee = User::factory()->employee()->create();
    $company = Company::factory()->createOne();
    $company->employees()->attach($employee->getKey());

    return $employee;
};

describe('STORY 317 — redirect to profile home', function () use ($adminDashboard, $makeEmployee): void {
    it('redirects an employee hitting the admin panel to their own app home', function () use ($adminDashboard, $makeEmployee): void {
        $employee = $makeEmployee();
        actingAs($employee);

        $response = get(route($adminDashboard));

        $response->assertStatus(302);
        expect($response->headers->get('Location'))
            ->toContain('/app')
            ->not->toContain('/admin');
    });

    it('redirects a consultant hitting the admin panel to the consultant home', function () use ($adminDashboard): void {
        actingAsConsultant();

        $response = get(route($adminDashboard));

        $response->assertStatus(302);

        expect($response->headers->get('Location'))->toContain('/consultant');
    });

    it('lets an admin access the admin panel without redirecting', function () use ($adminDashboard): void {
        actingAsAdmin();

        get(route($adminDashboard))->assertOk();
    });
});

describe('STORY 317 — company (tenant) the user is not part of', function () use ($userDashboard, $makeEmployee): void {
    it('redirects a user opening a company they do not belong to towards their own home', function () use ($userDashboard, $makeEmployee): void {
        $ownCompany = null;
        $employee = $makeEmployee($ownCompany);
        $otherCompany = Company::factory()->createOne(['slug' => 'outra-empresa']);
        actingAs($employee);

        $response = get(route($userDashboard, ['tenant' => $otherCompany->slug]));

        $response->assertStatus(302);
        expect($response->headers->get('Location'))
            ->toContain($ownCompany->slug)
            ->not->toContain($otherCompany->slug);
    });

    it('does not create a redirect loop: the resolved home is reachable', function () use ($userDashboard, $makeEmployee): void {
        // The billing/anamnese tenant middleware are irrelevant to this loop
        // check, so let them pass through.
        $passthrough = new class
        {
            public function handle($request, Closure $next)
            {
                return $next($request);
            }
        };
        app()->instance(RedirectUserIfNotSubscribed::class, $passthrough);
        app()->instance(RedirectIfAnamneseNotCompleted::class, $passthrough);

        $ownCompany = null;
        $employee = $makeEmployee($ownCompany);
        $otherCompany = Company::factory()->createOne(['slug' => 'outra-empresa']);
        actingAs($employee);

        $this->followingRedirects()
            ->get(route($userDashboard, ['tenant' => $otherCompany->slug]))
            ->assertOk();
    });
});

describe('STORY 318 — preserve error for unauthenticated / unmapped profiles', function () use ($adminDashboard): void {
    it('keeps sending unauthenticated users to the login page', function () use ($adminDashboard): void {
        $response = get(route($adminDashboard));

        $response->assertStatus(302);

        expect($response->headers->get('Location'))->toContain('login');
    });

    it('shows the default 403 for an authenticated user whose profile has no mapped home', function () use ($adminDashboard): void {
        Log::spy();

        $user = User::factory()->user()->create();
        actingAs($user);

        get(route($adminDashboard))->assertForbidden();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message): bool => str_contains($message, 'without a mapped profile home'));
    });
});

describe('profile → home mapping is defined for every profile', function (): void {
    it('maps each profile to an accessible home panel (no loop possible)', function (): void {
        $company = Company::factory()->createOne();

        $employee = User::factory()->employee()->create();
        $company->employees()->attach($employee->getKey());

        $admin = User::factory()->admin()->create();
        $consultant = User::factory()->consultant()->create();
        $owner = User::factory()->companyOwner()->create();
        Company::factory()->recycle($owner)->create();
        $orphan = User::factory()->user()->create();

        expect(FilamentPanel::homePanelFor($employee))->toBe(FilamentPanel::User)
            ->and(FilamentPanel::homePanelFor($admin))->toBe(FilamentPanel::Admin)
            ->and(FilamentPanel::homePanelFor($consultant))->toBe(FilamentPanel::Consultant)
            ->and(FilamentPanel::homePanelFor($owner))->toBe(FilamentPanel::Company)
            ->and(FilamentPanel::homePanelFor($orphan))->toBeNull();

        // The resolved panel is always one the user can actually access, which
        // is what guarantees the redirect never bounces back into a denial.
        foreach ([$employee, $admin, $consultant, $owner] as $user) {
            $panel = FilamentPanel::homePanelFor($user);
            expect(FilamentPanel::canAccessPanel(filament()->getPanel($panel->value), $user))->toBeTrue();
        }
    });
});
