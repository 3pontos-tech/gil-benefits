<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Actions\Credit\GrantExtraCredit;
use TresPontosTech\Billing\Core\DTOs\GrantCreditDTO;
use TresPontosTech\Billing\Core\Models\CreditGrant;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\CreditGrants\Pages\ListCreditGrants;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->admin = actingAsAdmin();
});

function makeGrant(): CreditGrant
{
    $company = Company::factory()->create();

    return resolve(GrantExtraCredit::class)->handle(new GrantCreditDTO(
        adminUserId: (string) auth()->id(),
        company: $company,
        quantity: 3,
        justification: 'Cortesia',
    ));
}

it('renders and lists the grants', function (): void {
    $grant = makeGrant();

    livewire(ListCreditGrants::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$grant]);
});

it('revokes a grant, soft-deleting the grant and its available credits', function (): void {
    $grant = makeGrant();

    livewire(ListCreditGrants::class)
        ->callTableAction('revoke', $grant);

    expect(CreditGrant::query()->find($grant->getKey()))->toBeNull()
        ->and(UserCredit::query()->where('grant_id', $grant->getKey())->count())->toBe(0);
});
