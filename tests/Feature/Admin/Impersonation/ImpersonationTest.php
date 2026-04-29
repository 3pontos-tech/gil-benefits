<?php

declare(strict_types=1);

use App\Models\Users\User;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;
use TresPontosTech\Admin\Filament\Resources\Consultants\Pages\ListConsultants;
use TresPontosTech\Admin\Listeners\LogImpersonationEndedListener;
use TresPontosTech\Admin\Listeners\LogImpersonationStartedListener;
use TresPontosTech\Admin\Models\ImpersonationLog;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

// ─── T5: canAccessTenant ──────────────────────────────────────────────────────

describe('canAccessTenant', function (): void {
    it('allows admin to access any company as tenant', function (): void {
        $admin = actingAsAdmin();
        $company = Company::factory()->create();

        expect($admin->canAccessTenant($company))->toBeTrue();
    });

    it('blocks non-admin from accessing a company they are not a member of', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Roles::User->value);
        $company = Company::factory()->create();

        expect($user->canAccessTenant($company))->toBeFalse();
    });

    it('allows non-admin to access companies they are members of', function (): void {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $company->employees()->attach($user->id, ['role' => Roles::Employee->value, 'active' => true]);

        expect($user->canAccessTenant($company))->toBeTrue();
    });

    it('returns all companies for admin in getTenants()', function (): void {
        $admin = actingAsAdmin();
        Company::factory()->count(3)->create();

        $tenants = $admin->getTenants(filament()->getCurrentOrDefaultPanel());

        expect($tenants->count())->toBeGreaterThanOrEqual(3);
    });
});

// ─── T1+T2: Impersonate action ───────────────────────────────────────────────

describe('impersonate action', function (): void {
    it('shows impersonate action in the consultants table for admins', function (): void {
        actingAsAdmin();
        $consultant = Consultant::factory()->create();

        livewire(ListConsultants::class)
            ->assertTableActionExists('impersonate', record: $consultant);
    });

    it('only admins can impersonate', function (): void {
        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $regular->assignRole(Roles::User->value);

        expect($admin->canImpersonate())->toBeTrue()
            ->and($regular->canImpersonate())->toBeFalse();
    });

    it('non-admin users can be impersonated', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Roles::User->value);

        expect($user->canBeImpersonated())->toBeTrue();
    });

    it('admins cannot be impersonated', function (): void {
        $admin = User::factory()->admin()->create();

        expect($admin->canBeImpersonated())->toBeFalse();
    });
});

// ─── T3: Impersonation logs ───────────────────────────────────────────────────

describe('impersonation logs', function (): void {
    it('creates a log entry when impersonation starts', function (): void {
        $admin = User::factory()->admin()->create();
        $consultant = User::factory()->create();

        $listener = new LogImpersonationStartedListener;
        $listener->handle(new EnterImpersonation($admin, $consultant));

        assertDatabaseHas('impersonation_logs', [
            'admin_id' => $admin->id,
            'impersonated_user_id' => $consultant->id,
        ]);
    });

    it('records started_at and null ended_at on log creation', function (): void {
        $admin = User::factory()->admin()->create();
        $consultant = User::factory()->create();

        $listener = new LogImpersonationStartedListener;
        $listener->handle(new EnterImpersonation($admin, $consultant));

        $log = ImpersonationLog::first();
        expect($log->started_at)->not->toBeNull()
            ->and($log->ended_at)->toBeNull();
    });

    it('updates ended_at when impersonation ends', function (): void {
        $admin = User::factory()->admin()->create();
        $consultant = User::factory()->create();

        ImpersonationLog::create([
            'admin_id' => $admin->id,
            'impersonated_user_id' => $consultant->id,
            'started_at' => now()->subMinutes(5),
        ]);

        $listener = new LogImpersonationEndedListener;
        $listener->handle(new LeaveImpersonation($admin, $consultant));

        expect(ImpersonationLog::first()->ended_at)->not->toBeNull();
    });

    it('only closes the most recent open log when leaving', function (): void {
        $admin = User::factory()->admin()->create();
        $consultant = User::factory()->create();

        $closedAt = now()->subMinutes(50);

        $old = ImpersonationLog::create([
            'admin_id' => $admin->id,
            'impersonated_user_id' => $consultant->id,
            'started_at' => now()->subHour(),
        ]);
        $old->ended_at = $closedAt;
        $old->save();

        $open = ImpersonationLog::create([
            'admin_id' => $admin->id,
            'impersonated_user_id' => $consultant->id,
            'started_at' => now()->subMinutes(5),
        ]);

        $listener = new LogImpersonationEndedListener;
        $listener->handle(new LeaveImpersonation($admin, $consultant));

        expect($open->fresh()->ended_at)->not->toBeNull();
        expect($old->fresh()->ended_at->toDateTimeString())->toBe($closedAt->toDateTimeString());
    });
});
