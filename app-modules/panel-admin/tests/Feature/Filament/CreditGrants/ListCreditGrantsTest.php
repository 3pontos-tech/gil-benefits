<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\CreditGrant;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Clusters\Credits\Resources\CreditGrants\CreditGrantResource;
use TresPontosTech\PanelAdmin\Filament\Clusters\Credits\Resources\CreditGrants\Pages\ListCreditGrants;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\EditCompany;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

/**
 * Grant credits to a fresh company through the admin panel action (the real flow),
 * returning the created grant.
 */
function grantViaPanel(int $quantity = 3, string $justification = 'Cortesia'): CreditGrant
{
    $company = Company::factory()->create();

    livewire(EditCompany::class, ['record' => $company->getRouteKey()])
        ->callAction('grant_extra_credit', data: [
            'quantity' => $quantity,
            'justification' => $justification,
        ])
        ->assertHasNoFormErrors();

    return CreditGrant::query()->where('company_id', $company->getKey())->latest()->firstOrFail();
}

it('lists a grant created from the company page', function (): void {
    $grant = grantViaPanel();

    livewire(ListCreditGrants::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$grant]);
});

it('revokes the available credits from the list but keeps the grant', function (): void {
    $grant = grantViaPanel(3);

    livewire(ListCreditGrants::class)
        ->callTableAction('revoke', $grant);

    expect(CreditGrant::query()->find($grant->getKey()))->not->toBeNull()
        ->and(UserCredit::query()->where('grant_id', $grant->getKey())->count())->toBe(0)
        ->and(UserCredit::onlyTrashed()->where('grant_id', $grant->getKey())->count())->toBe(3);
});

it('revoking from the list leaves credits already in appointments', function (): void {
    $grant = grantViaPanel(3);

    // Simulate one credit already consumed by an appointment.
    $consumed = UserCredit::query()->where('grant_id', $grant->getKey())->first();
    $consumed->update(['status' => UserCreditStatusEnum::Used]);

    livewire(ListCreditGrants::class)
        ->callTableAction('revoke', $grant);

    expect(UserCredit::query()->where('grant_id', $grant->getKey())->count())->toBe(1)
        ->and(UserCredit::query()->where('grant_id', $grant->getKey())->first()->is($consumed))->toBeTrue();
});

it('counts revoked credits per grant, not per company', function (): void {
    $company = Company::factory()->create();

    // Two grants on the SAME company, both through the panel; only the first is revoked.
    livewire(EditCompany::class, ['record' => $company->getRouteKey()])
        ->callAction('grant_extra_credit', data: ['quantity' => 5, 'justification' => 'revogado'])
        ->assertHasNoActionErrors();

    livewire(EditCompany::class, ['record' => $company->getRouteKey()])
        ->callAction('grant_extra_credit', data: ['quantity' => 2, 'justification' => 'intacto'])
        ->assertHasNoActionErrors();

    $revoked = CreditGrant::query()->where('company_id', $company->getKey())->where('justification', 'revogado')->firstOrFail();
    $intact = CreditGrant::query()->where('company_id', $company->getKey())->where('justification', 'intacto')->firstOrFail();

    livewire(ListCreditGrants::class)
        ->callTableAction('revoke', $revoked);

    $rows = CreditGrantResource::getEloquentQuery()
        ->whereIn('id', [$revoked->getKey(), $intact->getKey()])
        ->get()
        ->keyBy('id');

    // Scoped by grant_id: the untouched grant stays at 0 even sharing the company.
    expect((int) $rows[$revoked->getKey()]->revoked_credits_count)->toBe(5)
        ->and((int) $rows[$intact->getKey()]->revoked_credits_count)->toBe(0);
});
