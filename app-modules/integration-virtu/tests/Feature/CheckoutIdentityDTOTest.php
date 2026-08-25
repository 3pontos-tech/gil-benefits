<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\IntegrationVirtu\DTO\CheckoutIdentityDTO;

function paramsFor(Company|User $billable): array
{
    return CheckoutIdentityDTO::fromBillable($billable)->toQueryParams();
}

it('pre-fills the buyer phone when the user has one', function (): void {
    $user = User::factory()->create();
    Detail::factory()->for($user)->create(['phone_number' => '(11) 99999-0000']);

    expect(paramsFor($user->refresh()))
        ->toHaveKey('phone', '11999990000');
});

it('omits the phone when the user has none', function (): void {
    $user = User::factory()->create();
    Detail::factory()->for($user)->create(['phone_number' => null]);

    expect(paramsFor($user->refresh()))->not->toHaveKey('phone');
});

it('omits the phone when the user has no detail record at all', function (): void {
    expect(paramsFor(User::factory()->create()))->not->toHaveKey('phone');
});

it('uses the owner phone when the buyer is a company', function (): void {
    $owner = User::factory()->create();
    Detail::factory()->for($owner)->create(['phone_number' => '11988887777']);

    $company = Company::factory()->recycle($owner)->create();

    expect(paramsFor($company->refresh()))->toHaveKey('phone', '11988887777');
});
