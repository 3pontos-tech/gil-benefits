<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\PanelAdmin\Filament\Clusters\Financial\FinancialCluster;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\RevenueDashboard;
use TresPontosTech\PanelAdmin\Filament\Resources\Permissions\Actions\AssignRoleAction;
use TresPontosTech\Permissions\Role;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\actingAs;

/**
 * Cria um usuário verificado com um único papel global, sem nenhum vínculo de
 * empresa — a forma mais crua de checar o que cada papel abre por si só.
 */
function userWithRole(Roles $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user->fresh();
}

function canReach(User $user, FilamentPanel $panel): bool
{
    return FilamentPanel::canAccessPanel(filament()->getPanel($panel->value), $user);
}

describe('gate do cockpit financeiro', function (): void {
    it('libera os perfis financeiros', function (Roles $role): void {
        actingAs(userWithRole($role));

        expect(FinancialCluster::canAccess())->toBeTrue();
    })->with([
        'financeiro' => Roles::Financial,
        'customer success' => Roles::CustomerSuccess,
        'super admin' => Roles::SuperAdmin,
    ]);

    it('bloqueia quem não é perfil financeiro, inclusive Admin', function (Roles $role): void {
        actingAs(userWithRole($role));

        expect(FinancialCluster::canAccess())->toBeFalse();
    })->with([
        'admin comum' => Roles::Admin,
        'consultor' => Roles::Consultant,
        'funcionário' => Roles::Employee,
        'usuário' => Roles::User,
    ]);

    it('bloqueia visitante não autenticado', function (): void {
        expect(FinancialCluster::canAccess())->toBeFalse();
    });
});

describe('regressão de acesso aos 5 painéis', function (): void {
    it('deixa os perfis financeiros entrarem apenas no painel Admin', function (Roles $role): void {
        $user = userWithRole($role);

        expect(canReach($user, FilamentPanel::Admin))->toBeTrue()
            ->and(canReach($user, FilamentPanel::User))->toBeFalse()
            ->and(canReach($user, FilamentPanel::Consultant))->toBeFalse();
    })->with([
        'financeiro' => Roles::Financial,
        'customer success' => Roles::CustomerSuccess,
    ]);

    it('exige e-mail verificado no painel Admin também para o perfil financeiro', function (): void {
        $user = userWithRole(Roles::Financial);
        $user->forceFill(['email_verified_at' => null])->save();

        expect(canReach($user->fresh(), FilamentPanel::Admin))->toBeFalse();
    });

    it('mantém o acesso dos papéis que já existiam', function (Roles $role, bool $admin, bool $consultant): void {
        $user = userWithRole($role);

        expect(canReach($user, FilamentPanel::Admin))->toBe($admin)
            ->and(canReach($user, FilamentPanel::Consultant))->toBe($consultant);
    })->with([
        'super admin' => [Roles::SuperAdmin, true, true],
        'admin' => [Roles::Admin, true, true],
        'consultor' => [Roles::Consultant, false, true],
        'usuário' => [Roles::User, false, false],
        'funcionário' => [Roles::Employee, false, false],
    ]);

    it('mantém o painel Guest aberto para todos', function (Roles $role): void {
        expect(canReach(userWithRole($role), FilamentPanel::Guest))->toBeTrue();
    })->with([
        'financeiro' => Roles::Financial,
        'customer success' => Roles::CustomerSuccess,
        'usuário' => Roles::User,
    ]);
});

describe('atribuição dos papéis novos', function (): void {
    it('expõe os perfis financeiros na lista de papéis globais atribuíveis', function (): void {
        $action = new ReflectionMethod(
            AssignRoleAction::class,
            'availableRoles'
        );

        $roles = $action->invoke(
            AssignRoleAction::make()
        );

        expect($roles)->toContain(Roles::Financial)
            ->and($roles)->toContain(Roles::CustomerSuccess);
    });

    it('cria os papéis novos no sync:permissions', function (): void {
        $this->artisan('sync:permissions')->assertSuccessful();

        expect(Role::query()->where('name', Roles::Financial->value)->exists())->toBeTrue()
            ->and(Role::query()->where('name', Roles::CustomerSuccess->value)->exists())->toBeTrue();
    });
});

describe('navegação', function (): void {
    it('ocupa um item só na sidebar, dentro de Relatórios', function (): void {
        // Cinco telas do mesmo assunto listadas lá fora afogariam o grupo.
        $items = FinancialCluster::getNavigationItems();

        expect($items)->toHaveCount(1)
            ->and($items[0]->getLabel())->toBe('Financeiro')
            ->and(FinancialCluster::getNavigationGroup())->toBe('Relatórios');
    });

    it('leva direto ao dashboard, e não à página-índice do cluster', function (): void {
        expect(FinancialCluster::getNavigationItems()[0]->getUrl())
            ->toBe(RevenueDashboard::getUrl());
    });

    it('alcança as outras quatro telas por dentro do dashboard', function (): void {
        actingAsFinancial();

        $this->get(RevenueDashboard::getUrl())
            ->assertOk()
            ->assertSee('Empresas e Contratos')
            ->assertSee('Cobranças')
            ->assertSee('Consultorias')
            ->assertSee('Usuários');
    });
});
